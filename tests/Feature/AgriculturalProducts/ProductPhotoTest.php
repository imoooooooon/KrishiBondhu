<?php

namespace Tests\Feature\AgriculturalProducts;

use App\Models\AgriculturalProduct;
use App\Models\Role;
use App\Services\CropListingPhotoService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** F25: one optional product photo: re-encoded privately, replaced or removed safely, served only while visible. */
class ProductPhotoTest extends TestCase
{
    use AgriculturalProductHelpers, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake(CropListingPhotoService::DISK);
    }

    private function disk()
    {
        return Storage::disk(CropListingPhotoService::DISK);
    }

    public function test_a_product_can_be_added_with_a_photo_that_is_re_encoded_privately(): void
    {
        $seller = $this->seller();

        $this->actingAs($seller)->post(route('seller.products.store'), $this->validForm([
            'photo' => UploadedFile::fake()->image('urea.png', 1200, 675),
        ]))->assertSessionHasNoErrors();

        $product = AgriculturalProduct::sole();
        $this->assertTrue($product->hasPhoto());
        $this->assertStringStartsWith(AgriculturalProduct::PHOTO_DIRECTORY.'/', $product->photo_path);
        $this->assertStringEndsWith('.jpg', $product->photo_path); // re-encoded as JPEG, whatever was uploaded
        $this->assertSame([1200, 675], [$product->photo_width, $product->photo_height]);
        $this->assertCount(2, $this->disk()->files(AgriculturalProduct::PHOTO_DIRECTORY));

        $this->get($product->photoUrl('thumb'))->assertOk()->assertHeader('Content-Type', 'image/jpeg')
            ->assertHeader('Cache-Control', 'no-store, private');
        $this->get(route('shop.index'))->assertSee($product->photoUrl('thumb'), false);
        $this->get(route('shop.show', $product))->assertSee($product->photoUrl('display'), false);
    }

    public function test_the_photo_is_optional(): void
    {
        $this->actingAs($this->seller())->post(route('seller.products.store'), $this->validForm())->assertSessionHasNoErrors();

        $product = AgriculturalProduct::sole();
        $this->assertFalse($product->hasPhoto());
        $this->assertNull($product->photoUrl());
        $this->get(route('shop.show', $product))->assertOk()->assertSee('No photo yet');
    }

    public function test_unsafe_or_unsupported_files_are_refused_and_nothing_is_saved(): void
    {
        $seller = $this->seller();

        foreach ([
            UploadedFile::fake()->create('virus.php', 10, 'application/x-php'),
            UploadedFile::fake()->create('fake.jpg', 10, 'image/jpeg'),           // a .jpg name, but not an image
            UploadedFile::fake()->image('big.jpg', 100, 100)->size(6000),         // over 5 MB
            UploadedFile::fake()->image('huge.jpg', 6001, 10),                    // too many pixels on one side
            UploadedFile::fake()->create('photo.gif', 10, 'image/gif'),
        ] as $file) {
            $this->actingAs($seller)->post(route('seller.products.store'), $this->validForm(['photo' => $file]))
                ->assertSessionHasErrors('photo');
        }

        $this->assertSame(0, AgriculturalProduct::count());
        $this->assertSame([], $this->disk()->allFiles());
    }

    public function test_a_new_photo_replaces_the_old_one_and_the_old_files_are_deleted(): void
    {
        $seller = $this->seller();
        $product = AgriculturalProduct::factory()->for($seller, 'seller')->withPhoto()->create();
        $oldPaths = $product->photoPaths();

        $this->actingAs($seller)->patch(route('seller.products.update', $product), $this->editForm($product, [
            'photo' => UploadedFile::fake()->image('new.jpg', 800, 450),
        ]))->assertSessionHasNoErrors();

        $fresh = $product->fresh();
        $this->assertNotSame($oldPaths[0], $fresh->photo_path);
        $this->assertSame(800, $fresh->photo_width);
        foreach ($oldPaths as $path) {
            $this->disk()->assertMissing($path);
        }
        $this->disk()->assertExists($fresh->photo_path);
        $this->assertSame(2, $fresh->version);
    }

    public function test_the_photo_can_be_removed_and_editing_without_a_photo_keeps_it(): void
    {
        $seller = $this->seller();
        $product = AgriculturalProduct::factory()->for($seller, 'seller')->withPhoto()->create();
        $paths = $product->photoPaths();

        // A normal save without a file keeps the photo.
        $this->actingAs($seller)->patch(route('seller.products.update', $product), $this->editForm($product, ['stock_quantity' => '12']))
            ->assertSessionHasNoErrors();
        $this->assertSame($paths[0], $product->fresh()->photo_path);

        $product->refresh();
        $this->actingAs($seller)->patch(route('seller.products.update', $product), $this->editForm($product, ['remove_photo' => '1']))
            ->assertSessionHasNoErrors();

        $this->assertFalse($product->fresh()->hasPhoto());
        $this->assertSame([], $this->disk()->allFiles());
    }

    public function test_a_save_from_an_old_tab_keeps_the_newer_photo_and_deletes_its_own_upload(): void
    {
        $seller = $this->seller();
        $product = AgriculturalProduct::factory()->for($seller, 'seller')->withPhoto()->create();
        $tabA = $this->editForm($product, ['stock_quantity' => '35']);
        $tabB = $this->editForm($product, ['photo' => UploadedFile::fake()->image('stale.jpg', 400, 225)]);

        $this->actingAs($seller)->patch(route('seller.products.update', $product), $tabA)->assertSessionHasNoErrors();
        $this->actingAs($seller)->patch(route('seller.products.update', $product), $tabB)->assertStatus(409);

        $this->assertSame($product->photo_path, $product->fresh()->photo_path);
        $this->assertCount(2, $this->disk()->allFiles(), 'the stale request’s new photo files were deleted');
    }

    public function test_a_hidden_or_removed_products_photo_is_private(): void
    {
        $seller = $this->seller();
        $product = AgriculturalProduct::factory()->for($seller, 'seller')->unpublished()->withPhoto()->create();

        $this->get($product->photoUrl('thumb'))->assertNotFound();
        $this->actingAs($this->seller())->get($product->photoUrl('display'))->assertNotFound();
        $this->actingAs($this->userWith(Role::BUYER))->get($product->photoUrl('display'))->assertNotFound();
        // Its own seller can still see it in the workspace.
        $this->actingAs($seller)->get($product->photoUrl('display'))->assertOk();
        $this->actingAs($seller)->get(route('seller.products.show', $product))->assertSee($product->photoUrl('display'), false);

        $product->delete();
        $this->actingAs($seller)->get(route('shop.photo', ['product' => $product->id, 'variant' => 'display']))->assertNotFound();
    }

    public function test_only_the_two_variants_can_be_requested(): void
    {
        $product = AgriculturalProduct::factory()->withPhoto()->create();

        $this->get('/shop/products/'.$product->id.'/photo/original')->assertNotFound();
        $this->get('/shop/products/'.$product->id.'/photo/..%2F..%2F.env')->assertNotFound();
        $this->get(route('shop.photo', ['product' => AgriculturalProduct::factory()->create()->id, 'variant' => 'thumb']))->assertNotFound();
    }

    public function test_photo_paths_are_never_in_serialised_output(): void
    {
        $product = AgriculturalProduct::factory()->withPhoto()->create();

        $this->assertArrayNotHasKey('photo_path', $product->toArray());
        $this->assertArrayNotHasKey('photo_thumbnail_path', $product->toArray());
    }

    public function test_the_database_refuses_half_a_photo(): void
    {
        $product = AgriculturalProduct::factory()->withPhoto()->create();

        $this->expectException(QueryException::class);
        DB::table('agricultural_products')->where('id', $product->id)->update(['photo_thumbnail_path' => null]);
    }
}
