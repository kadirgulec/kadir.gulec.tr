<?php

namespace App\Providers;

use App\Enums\Permission;
use App\Http\Middleware\EnsureAdminAccess;
use App\Models\Comment;
use App\Models\Follow;
use App\Models\Goal;
use App\Models\MonthlyReview;
use App\Models\Note;
use App\Models\Post;
use App\Models\Project;
use App\Models\User;
use App\Models\Watchable;
use App\Support\Notifications\Announcements;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureAuthorization();
        $this->configureMorphMap();

        Announcements::register();
    }

    /**
     * Short, stable names in polymorphic columns (devlog, roles, comments,
     * follows) instead of class names, so renaming a class never breaks data.
     */
    protected function configureMorphMap(): void
    {
        Relation::enforceMorphMap([
            'user' => User::class,
            'project' => Project::class,
            'post' => Post::class,
            'note' => Note::class,
            'watchable' => Watchable::class,
            'goal' => Goal::class,
            'monthly_review' => MonthlyReview::class,
            'comment' => Comment::class,
            'follow' => Follow::class,
        ]);
    }

    /**
     * The admin role may do everything; every other role only what its
     * permissions allow.
     */
    protected function configureAuthorization(): void
    {
        Gate::before(fn (User $user): ?bool => $user->isAdmin() ? true : null);

        // Tags are shared by posts and notes; managing either one is enough.
        Gate::define('manage-tags', fn (User $user): bool => $user->can(Permission::ManagePosts->value) || $user->can(Permission::ManageNotes->value));

        // Livewire update requests go to /livewire/update; this re-runs the
        // admin guard there for components that were rendered under /admin.
        Livewire::addPersistentMiddleware([EnsureAdminAccess::class]);
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(8)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
