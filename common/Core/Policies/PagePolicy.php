<?php namespace Common\Core\Policies;

use App\User;
use Common\Pages\CustomPage;

class PagePolicy extends BasePolicy
{
    public function index(User $user)
    {
        return $user->hasPermission('custom_pages.view');
    }

    public function show(User $user)
    {
        return $user->hasPermission('custom_pages.view');
    }

    public function store(User $user)
    {
        return $this->storeWithCountRestriction($user, CustomPage::class, 'link_custom_pages');
    }

    public function update(User $user)
    {
        return $user->hasPermission('custom_pages.update');
    }

    public function destroy(User $user, $pageIds)
    {
        if ($user->hasPermission('custom_pages.delete')) {
            return true;
        } else {
            $dbCount = app(CustomPage::class)
                ->whereIn('id', $pageIds)
                ->where('user_id', $user->id)
                ->count();
            return $dbCount === count($pageIds);
        }
    }
}