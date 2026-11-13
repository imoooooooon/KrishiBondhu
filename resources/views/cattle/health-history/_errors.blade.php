{{-- Linked error summary for the health-entry forms. --}}
@if ($errors->any())
    <x-ui.alert tone="danger" role="alert" tabindex="-1" data-error-summary
        :title="trans_choice('Please fix :count problem.|Please fix :count problems.', count($errors->all()), ['count' => count($errors->all())])">
        <ul class="cattle-list">
            @foreach ($errors->getMessages() as $key => $messages)
                @foreach ($messages as $message)
                    <li><a href="#{{ in_array($key, ['version', 'submission_token', 'cattle'], true) ? 'cattle-form' : 'field-'.$key }}">{{ $message }}</a></li>
                @endforeach
            @endforeach
        </ul>
    </x-ui.alert>
@endif
