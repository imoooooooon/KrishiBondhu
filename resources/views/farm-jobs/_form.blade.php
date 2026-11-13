{{--
    Fields shared by "Post a job" and "Edit job" (F20). Pass $job when editing; it is null for a new job.
    There is no farmer_id or status field: the controller sets the owner, and closing has its own button.
--}}
@php
    $job = $job ?? null;
@endphp

<x-ui.field name="title" :label="__('Job title')" :value="$job?->title" :hint="__('A short name for the work, like: Harvest Aman rice, 2 bigha')"
    maxlength="150" required />

<x-ui.select name="work_type" :label="__('Work type')" :options="\App\Models\FarmJob::workTypeLabels()" :value="$job?->work_type"
    :placeholder="__('Choose the type of work')" required />

<x-ui.textarea name="description" :label="__('Description')" :value="$job?->description" rows="5"
    :hint="__('What the worker must do, the hours, and tools to bring. For a task wage, describe the whole task here.')" required />

<x-ui.field name="location" :label="__('Location')" :value="$job?->location" :hint="__('Village, union and upazila, so workers know where to come')"
    maxlength="255" required />

<div class="grid-2">
    <x-ui.field name="work_date" type="date" :label="__('Work date')" :value="$job?->work_date?->toDateString()"
        :min="\App\Models\FarmJob::today()" required />

    <x-ui.field name="required_workers" type="number" :label="__('Workers needed')" :value="$job?->required_workers"
        min="1" max="1000" step="1" inputmode="numeric" required />
</div>

<x-ui.select name="wage_basis" :label="__('Wage basis')" :options="\App\Models\FarmJob::wageBasisLabels()" :value="$job?->wage_basis"
    :placeholder="__('Choose how the wage is counted')"
    :hint="__('Daily: paid for each day of work. Task-based: one amount for finishing the task you describe. Unit-based: paid for each unit finished, like each bag.')"
    data-wage-basis required />

<x-ui.field name="proposed_wage" type="number" :label="__('Proposed wage per worker')" :value="$job?->proposed_wage" prefix="৳"
    :hint="__('The amount in taka you offer each worker, for the wage basis above')"
    min="1" step="0.01" inputmode="decimal" required />

{{-- Needed only for unit-based wages. public/js/farm-jobs.js hides it for the other two; without JavaScript it stays visible. --}}
<div data-unit-label-field>
    <x-ui.field name="unit_label" :label="__('Unit')" :value="$job?->unit_label" maxlength="50"
        :hint="__('Only for unit-based wages: what is counted, like bag, maund or bigha')" />
</div>

<x-ui.textarea name="meal_details" :label="__('Meals').' ('.__('optional').')'" :value="$job?->meal_details" rows="2"
    :hint="__('Is food provided? For example: lunch and tea provided')" />

<x-ui.textarea name="transport_details" :label="__('Transport').' ('.__('optional').')'" :value="$job?->transport_details" rows="2"
    :hint="__('For example: van from the bazaar at 6 am, or workers arrange their own transport')" />
