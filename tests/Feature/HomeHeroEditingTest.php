<?php

use App\Models\User;
use App\Support\HomeHero;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Storage::fake('local');
});

test('only admins can save the homepage headline', function () {
    $data = ['headline' => 'Launch your software', 'highlights' => ['Find your audience.', 'Reach developers.']];

    $this->patch(route('admin.home-hero.update'), $data)->assertRedirect(route('login'));
    $this->actingAs(User::factory()->create())
        ->patch(route('admin.home-hero.update'), $data)->assertForbidden();
    Storage::disk('local')->assertMissing('home-hero.json');
});

test('admin changes persist and render as escaped public text', function () {
    Role::firstOrCreate(['name' => 'admin']);
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $data = ['headline' => 'Launch <script>alert(1)</script>', 'highlights' => ['Find your audience.', 'Reach developers.']];

    $this->actingAs($admin)->patch(route('admin.home-hero.update'), $data)
        ->assertRedirect(route('home'));
    expect(HomeHero::headline())->toBe(['headline' => $data['headline'], 'highlight' => $data['highlights'][0], 'highlights' => $data['highlights']]);
    expect(view('components.home-hero')->render())->toContain('home-hero-editor');

    auth()->logout();
    $html = view('components.home-hero')->render();
    expect($html)->toContain(e($data['headline']))->toContain($data['highlights'][0])->toContain($data['highlights'][1])
        ->not->toContain('<script>alert(1)</script>')->not->toContain('home-hero-editor');
});

test('invalid headlines cannot overwrite saved content', function () {
    Role::firstOrCreate(['name' => 'admin']);
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)->from(route('home'))
        ->patch(route('admin.home-hero.update'), ['headline' => '', 'highlights' => [str_repeat('a', 151)]])
        ->assertSessionHasErrors(['headline', 'highlights.0'], errorBag: 'homeHero');
    expect(HomeHero::headline()['headline'])->toBe(HomeHero::DEFAULT_HEADLINE['headline']);
});


test('existing single highlighted text is preserved as the first rotating phrase', function () {
    Storage::disk('local')->put('home-hero.json', json_encode([
        'headline' => 'Get discovered by', 'highlight' => 'Potential users',
    ]));
    expect(HomeHero::headline()['highlights'])->toBe(['Potential users']);
});

test('empty duplicate and excessive phrase lists are rejected', function () {
    Role::firstOrCreate(['name' => 'admin']);
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $this->actingAs($admin);

    foreach ([[], [''], ['Users', 'Users'], array_fill(0, 21, 'Users')] as $phrases) {
        $this->from(route('home'))->patch(route('admin.home-hero.update'), [
            'headline' => 'Get discovered by', 'highlights' => $phrases,
        ])->assertSessionHasErrorsIn('homeHero');
    }
    Storage::disk('local')->assertMissing('home-hero.json');
});

test('description editing requires admin and preserves the headline', function () {
    $route = route('admin.home-hero.description.update');
    $this->patch($route, ['description' => 'New copy'])->assertRedirect(route('login'));
    $this->actingAs(User::factory()->create())->patch($route, ['description' => 'New copy'])->assertForbidden();
    Role::firstOrCreate(['name' => 'admin']);
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $headline = HomeHero::headline();
    $description = 'Discover <script>alert(1)</script> software.';
    $this->actingAs($admin)->patch($route, ['description' => $description])->assertRedirect(route('home'));
    expect(HomeHero::description())->toBe($description);
    expect(HomeHero::headline())->toBe($headline);
    expect(view('components.home-hero')->render())->toContain('home-hero-description-editor');
    HomeHero::saveHeadline(['headline' => 'New headline']);
    expect(HomeHero::description())->toBe($description);
    auth()->logout();
    expect(view('components.home-hero')->render())->toContain(e($description))
        ->not->toContain('<script>alert(1)</script>')->not->toContain('home-hero-description-editor');
});

test('invalid descriptions preserve existing text', function () {
    Role::firstOrCreate(['name' => 'admin']);
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    foreach (['', str_repeat('a', 2001)] as $description) {
        $this->actingAs($admin)->from(route('home'))
            ->patch(route('admin.home-hero.description.update'), ['description' => $description])
            ->assertSessionHasErrors(['description'], errorBag: 'homeHeroDescription');
    }
    expect(HomeHero::description())->toBe(HomeHero::DEFAULT_DESCRIPTION);
});

test('feature editing is restricted to admins', function () {
    $route = route('admin.home-hero.features.update', ['feature' => 0]);
    $this->patch($route, ['label' => 'New feature'])->assertRedirect(route('login'));
    $this->actingAs(User::factory()->create())->patch($route, ['label' => 'New feature'])->assertForbidden();
    expect(HomeHero::features())->toBe(HomeHero::DEFAULT_FEATURES);
});

test('each feature saves independently and escapes public text', function () {
    Role::firstOrCreate(['name' => 'admin']);
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $headline = HomeHero::headline();
    HomeHero::saveDescription('Existing description');
    $this->actingAs($admin);
    foreach ([0, 1, 2] as $index) {
        $label = 'Feature '.$index.' <script>alert(1)</script>';
        $this->patch(route('admin.home-hero.features.update', ['feature' => $index]), ['label' => $label])
            ->assertRedirect(route('home'));
        expect(HomeHero::features()[$index])->toBe($label);
        expect(view('components.home-hero')->render())->toContain('hero-feature-editor-'.$index);
    }
    expect(HomeHero::headline())->toBe($headline);
    expect(HomeHero::description())->toBe('Existing description');
    auth()->logout();
    $html = view('components.home-hero')->render();
    foreach (HomeHero::features() as $label) {
        expect($html)->toContain(e($label));
    }
    expect($html)->not->toContain('<script>alert(1)</script>')->not->toContain('hero-feature-editor-');
});

test('feature labels reject invalid values and indexes', function () {
    Role::firstOrCreate(['name' => 'admin']);
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $this->actingAs($admin);
    foreach (['', str_repeat('a', 151)] as $label) {
        $this->from(route('home'))->patch(route('admin.home-hero.features.update', ['feature' => 1]), ['label' => $label])
            ->assertSessionHasErrors(['label'], errorBag: 'homeHeroFeature1');
    }
    $this->patch('/admin/settings/home-hero/features/3', ['label' => 'Invalid'])->assertNotFound();
    expect(HomeHero::features())->toBe(HomeHero::DEFAULT_FEATURES);
});

test('admin icon uploads are normalized and replace only their own icon', function () {
    Storage::fake('public');
    Role::firstOrCreate(['name' => 'admin']);
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $headline = HomeHero::headline();
    $this->actingAs($admin);
    $route = route('admin.home-hero.features.icon.update', ['feature' => 0]);
    $image = imagecreatetruecolor(100, 80);
    imagefill($image, 0, 0, imagecolorallocate($image, 255, 255, 255));
    imagefilledrectangle($image, 30, 20, 70, 60, imagecolorallocate($image, 255, 0, 0));
    ob_start(); imagepng($image); $png = ob_get_clean(); imagedestroy($image);
    $upload = fn () => \Illuminate\Http\UploadedFile::fake()->createWithContent('icon.png', $png);
    $this->post($route, ['icon' => $upload()])->assertRedirect(route('home'));
    $path = HomeHero::featureIcons()[0];
    Storage::disk('public')->assertExists($path);
    $normalized = imagecreatefromstring(Storage::disk('public')->get($path));
    expect(imagesx($normalized))->toBe(64);
    expect(imagesy($normalized))->toBe(64);
    expect(imagecolorat($normalized, 32, 32) & 0xffffff)->toBe(0);
    imagedestroy($normalized);
    $this->post($route, ['icon' => $upload()])->assertRedirect(route('home'));
    Storage::disk('public')->assertMissing($path);
    expect(HomeHero::headline())->toBe($headline);
    auth()->logout();
    expect(view('components.home-hero')->render())->toContain('mask: url(')->not->toContain('Upload feature icon');
});

test('feature icon uploads reject unauthorized users and unsafe files', function () {
    Storage::fake('public');
    $route = route('admin.home-hero.features.icon.update', ['feature' => 1]);
    $this->post($route)->assertRedirect(route('login'));
    $this->actingAs(User::factory()->create())->post($route)->assertForbidden();
    Role::firstOrCreate(['name' => 'admin']);
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $this->actingAs($admin)->from(route('home'))->post($route, [
        'icon' => \Illuminate\Http\UploadedFile::fake()->createWithContent('icon.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'),
    ])->assertSessionHasErrors(['icon'], errorBag: 'homeHeroIcon1');
    expect(HomeHero::featureIcons())->toBe([]);
});
