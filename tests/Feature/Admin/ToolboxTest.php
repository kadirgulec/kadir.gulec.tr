<?php

use App\Enums\ToolboxGroup;
use App\Models\Project;
use App\Models\Technology;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->admin()->create());
});

/**
 * @return list<string>
 */
function toolboxNames(ToolboxGroup $group): array
{
    return Technology::query()->where('toolbox_group', $group)->orderBy('toolbox_order')->pluck('name')->all();
}

it('keeps members out of the toolbox screen', function () {
    $this->actingAs(User::factory()->member()->create())
        ->get(route('admin.projects.toolbox'))
        ->assertNotFound();
});

it('lists the groups and the technologies outside the toolbox', function () {
    Technology::factory()->inToolbox(ToolboxGroup::Daily)->create(['name' => 'Laravel']);
    Technology::factory()->create(['name' => 'Vue']);

    $this->get(route('admin.projects.toolbox'))
        ->assertOk()
        ->assertSeeInOrder(['Her gün', 'Laravel', 'Ara sıra', 'Diller', 'Çantada değil', 'Vue']);
});

it('adds a new name at the end of a group', function () {
    Technology::factory()->inToolbox(ToolboxGroup::Languages)->create(['name' => 'Türkçe']);

    Livewire::test('pages::admin.projects.toolbox')
        ->set('name', ' Fransızca ')
        ->set('group', ToolboxGroup::Languages->value)
        ->call('add')
        ->assertHasNoErrors();

    expect(toolboxNames(ToolboxGroup::Languages))->toBe(['Türkçe', 'Fransızca']);
});

it('puts a technology a project already uses into the toolbox instead of a duplicate', function () {
    $vue = Technology::factory()->create(['name' => 'Vue']);

    Livewire::test('pages::admin.projects.toolbox')
        ->set('name', 'Vue')
        ->set('group', ToolboxGroup::Sometimes->value)
        ->call('add');

    expect(Technology::query()->count())->toBe(1)
        ->and($vue->fresh()->toolbox_group)->toBe(ToolboxGroup::Sometimes);
});

it('requires a name and a known group', function () {
    Livewire::test('pages::admin.projects.toolbox')
        ->set('name', '')
        ->set('group', 'yok')
        ->call('add')
        ->assertHasErrors(['name' => 'required', 'group']);
});

it('moves a technology to another group, at the end, or out of the toolbox', function () {
    Technology::factory()->inToolbox(ToolboxGroup::Sometimes)->create(['name' => 'Python']);
    $php = Technology::factory()->inToolbox(ToolboxGroup::Daily)->create(['name' => 'PHP']);

    $page = Livewire::test('pages::admin.projects.toolbox')->call('place', $php->id, ToolboxGroup::Sometimes->value);
    expect(toolboxNames(ToolboxGroup::Sometimes))->toBe(['Python', 'PHP']);

    $page->call('place', $php->id, null);
    expect($php->fresh()->toolbox_group)->toBeNull()
        ->and(toolboxNames(ToolboxGroup::Sometimes))->toBe(['Python']);
});

it('reorders a group by drag and drop and shows the order on the about page', function () {
    foreach (['PHP', 'Laravel', 'Livewire'] as $order => $name) {
        Technology::factory()->inToolbox(ToolboxGroup::Daily)->create(['name' => $name, 'toolbox_order' => $order]);
    }
    $other = Technology::factory()->inToolbox(ToolboxGroup::Sometimes)->create(['name' => 'Java', 'toolbox_order' => 0]);

    Livewire::test('pages::admin.projects.toolbox')
        ->call('sort', Technology::query()->where('name', 'Livewire')->value('id'), 0);

    expect(toolboxNames(ToolboxGroup::Daily))->toBe(['Livewire', 'PHP', 'Laravel'])
        ->and($other->fresh()->toolbox_order)->toBe(0);

    $this->get(route('about'))->assertSeeInOrder(['Livewire', 'PHP', 'Laravel']);
});

it('deletes only technologies no project uses', function () {
    $unused = Technology::factory()->inToolbox()->create(['name' => 'Yazım hatası']);
    $used = Technology::factory()->inToolbox()->create(['name' => 'Laravel']);
    Project::factory()->create()->technologies()->attach($used);

    Livewire::test('pages::admin.projects.toolbox')
        ->call('delete', $unused->id)
        ->call('delete', $used->id);

    expect(Technology::query()->pluck('name')->all())->toBe(['Laravel']);
});
