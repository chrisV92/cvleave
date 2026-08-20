<?php

use App\Filament\Pages\AdminGuide;
use App\Filament\Pages\EmployeeGuide;
use App\Filament\Platform\Pages\PlatformGuide;
use App\Models\Tenant;
use App\Models\User;
use Livewire\Livewire;

/**
 * The guides are one long Blade file each, with the navigation driven by an
 * Alpine expression living inside an HTML attribute. A single stray double
 * quote in there truncates the attribute and kills the whole component
 * silently — the page still returns 200 and simply stops working. These render
 * the pages so a broken guide fails here rather than in front of a customer.
 */
beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
});

it('renders the employee guide with its navigation intact', function () {
    actingInTenant(User::factory()->for($this->tenant)->create());

    Livewire::test(EmployeeGuide::class)
        ->assertOk()
        ->assertSee('kb-toc', escape: false)
        ->assertSee('x-data', escape: false);
});

it('renders the admin guide with its navigation intact', function () {
    actingInTenant(User::factory()->for($this->tenant)->admin()->create());

    Livewire::test(AdminGuide::class)
        ->assertOk()
        ->assertSee('kb-toc', escape: false)
        ->assertSee('x-data', escape: false);
});

it('renders the platform guide with its navigation intact', function () {
    $operator = User::factory()->for($this->tenant)->create(['is_platform_admin' => true]);

    actingInTenant($operator);

    Livewire::test(PlatformGuide::class)
        ->assertOk()
        ->assertSee('kb-toc', escape: false);
});

it('keeps every table of contents entry pointing at a real section', function () {
    $guides = [
        'resources/views/filament/pages/admin-guide.blade.php',
        'resources/views/filament/pages/employee-guide.blade.php',
        'resources/views/filament/platform/pages/platform-guide.blade.php',
    ];

    foreach ($guides as $guide) {
        $source = file_get_contents(base_path($guide));

        preg_match_all('/href="#([\w-]+)"/', $source, $links);
        preg_match_all('/section id="([\w-]+)"/', $source, $sections);

        // A link to a section that was renamed or removed scrolls nowhere, and
        // nothing else in the application would ever notice.
        expect(array_diff($links[1], $sections[1]))
            ->toBe([], "dead anchors in {$guide}");
    }
});
