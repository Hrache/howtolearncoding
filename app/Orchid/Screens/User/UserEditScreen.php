<?php

declare(strict_types=1);

namespace App\Orchid\Screens\User;

use Orchid\Screen\Action;
use Orchid\Screen\Layout;
use Orchid\Screen\Screen;
use Illuminate\Http\Request;
use Orchid\Access\UserSwitch;
use Orchid\Platform\Models\User;
use Orchid\Screen\Actions\Button;
use Orchid\Support\Facades\Alert;
use Orchid\Screen\Fields\Password;
use Orchid\Screen\Actions\DropDown;
use Illuminate\Support\Facades\Hash;
use Orchid\Screen\Actions\ModalToggle;
use App\Orchid\Layouts\User\UserEditLayout;
use App\Orchid\Layouts\User\UserRoleLayout;

class UserEditScreen extends Screen
{
  /**
   * Display header name.
   *
   * @var string
   */
  public $name = 'User';

  /**
   * Display header description.
   *
   * @var string
   */
  public $description = 'All registered users';

  /**
   * @var string
   */
  public $permission = 'platform.systems.users';

  /**
   * Query data.
   *
   * @param \Orchid\Platform\Models\User $user
   *
   * @return array
   */
  public function query(User $user): array
  {
      $user->load(['roles']);

      return [
          'user'       => $user,
          'permission' => $user->getStatusPermission(),
      ];
  }

  /**
   * Button commands.
   *
   * @return Action[]
   */
  public function commandBar(): array
  {
    return [

      DropDown::make(__('Settings'))
        ->icon('icon-open')
        ->list([
          Button::make(__('Login as user'))
            ->icon('icon-login')
            ->method('loginAs'),

          ModalToggle::make(__('Change Password'))
            ->icon('icon-lock-open')
            ->title(__('Change Password'))
            ->method('changePassword')
            ->modal('password'),
        ]),

      Button::make(__('Save'))
        ->icon('icon-check')
        ->method('save'),

      Button::make(__('Remove'))
        ->icon('icon-trash')
        ->method('remove'),
    ];
  }

  /**
   * @throws \Throwable
   *
   * @return Layout[]
   */
  public function layout(): array
  {
    return [
      UserEditLayout::class,
      UserRoleLayout::class,

      Layout::modal('password', [
        Layout::rows([
          Password::make('password')
            ->title(__('Password'))
            ->required()
            ->placeholder(__('Enter your password')),
        ]),
      ]),
    ];
  }

  /**
   * @param \Orchid\Platform\Models\User $user
   * @param \Illuminate\Http\Request     $request
   *
   * @return \Illuminate\Http\RedirectResponse
   */
  public function save(User $user, Request $request)
  {
    $permissions = $request->get('permissions', []);
    $roles = $request->input('user.roles', []);

    foreach ($permissions as $key => $value) {
      unset($permissions[$key]);
      $permissions[base64_decode($key)] = $value;
    }

    $user
      ->fill($request->get('user'))
      ->replaceRoles($roles)
      ->fill([
        'permissions' => $permissions,
      ])
      ->save();

    Alert::info(__('User was saved.'));

    return redirect()->route('platform.systems.users');
  }

  /**
   * @param User $user
   *
   * @throws \Exception
   *
   * @return \Illuminate\Http\RedirectResponse
   */
  public function remove(User $user)
  {
    $user->delete();

    Alert::info(__('User was removed'));

    return redirect()->route('platform.systems.users');
  }

  /**
   * @param User $user
   *
   * @return \Illuminate\Http\RedirectResponse
   */
  public function loginAs(User $user)
  {
    UserSwitch::loginAs($user);

    return redirect()->route(config('platform.index'));
  }

  /**
   * @param User    $user
   * @param Request $request
   *
   * @return \Illuminate\Http\RedirectResponse
   */
  public function changePassword(User $user, Request $request)
  {
    $user->password = Hash::make($request->get('password'));
    $user->save();

    Alert::info(__('User was saved.'));

    return back();
  }
}
