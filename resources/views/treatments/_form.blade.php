@csrf

<div class="kb-field">
    <label for="category">{{ __('Category') }}</label>
    <select id="category" name="category" required>
        <option value="crop" @selected(old('category', $treatment->category) === 'crop')>{{ __('Crop') }}</option>
        <option value="animal" @selected(old('category', $treatment->category) === 'animal')>{{ __('Animal') }}</option>
    </select>
    @error('category') <p class="kb-error">{{ $message }}</p> @enderror
</div>

<div class="kb-field">
    <label for="subject_name">{{ __('Crop or Animal Name') }}</label>
    <input id="subject_name" type="text" name="subject_name" value="{{ old('subject_name', $treatment->subject_name) }}" maxlength="120" required>
    @error('subject_name') <p class="kb-error">{{ $message }}</p> @enderror
</div>

<div class="kb-field">
    <label for="problem">{{ __('Problem / Symptoms') }}</label>
    <textarea id="problem" name="problem" rows="3" required>{{ old('problem', $treatment->problem) }}</textarea>
    @error('problem') <p class="kb-error">{{ $message }}</p> @enderror
</div>

<div class="kb-field">
    <label for="treatment_notes">{{ __('Treatment & Medicine Notes') }}</label>
    <textarea id="treatment_notes" name="treatment_notes" rows="3" required>{{ old('treatment_notes', $treatment->treatment_notes) }}</textarea>
    @error('treatment_notes') <p class="kb-error">{{ $message }}</p> @enderror
</div>

<div class="kb-field">
    <label for="treated_on">{{ __('Treatment Date') }}</label>
    <input id="treated_on" type="date" name="treated_on" value="{{ old('treated_on', $treatment->treated_on?->format('Y-m-d')) }}" required>
    @error('treated_on') <p class="kb-error">{{ $message }}</p> @enderror
</div>

<div class="kb-field">
    <label for="follow_up_date">{{ __('Follow-up Date (Optional)') }}</label>
    <input id="follow_up_date" type="date" name="follow_up_date" value="{{ old('follow_up_date', $treatment->follow_up_date?->format('Y-m-d')) }}">
    @error('follow_up_date') <p class="kb-error">{{ $message }}</p> @enderror
</div>

<div class="kb-field">
    <label for="status">{{ __('Status') }}</label>
    <select id="status" name="status" required>
        <option value="ongoing" @selected(old('status', $treatment->status ?? 'ongoing') === 'ongoing')>{{ __('Ongoing') }}</option>
        <option value="completed" @selected(old('status', $treatment->status) === 'completed')>{{ __('Completed') }}</option>
    </select>
    @error('status') <p class="kb-error">{{ $message }}</p> @enderror
</div>

<button type="submit" class="kb-btn kb-btn-primary">{{ $submitLabel }}</button>