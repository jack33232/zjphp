<?php
namespace ZJPHP\Model;

use ZJPHP\Base\ZJPHP;
use ZJPHP\Base\Component;
// Exceptions
use ZJPHP\Base\Exception\InvalidCallException;
// All Used Dataset
use ZJPHP\Model\Dataset\User as DataUser;
use ZJPHP\Model\Dataset\Group as DataGroup;
// Helpers
use ZJPHP\Base\Kit\ArrayHelper;
// Event
use ZJPHP\Model\Event\GroupBatchDeleteEvent;
use ZJPHP\Model\Event\GroupDeleteEvent;

class User extends Component
{
    const EVENT_LOGIN_SUCCESS = 'user_login_success';
    const EVENT_LOGIN_FAIL = 'user_login_fail';
    const EVENT_LOGOUT = 'user_logout';

    const USER_ACTION_PERMISSIONS_PREFIX = 'user_action_permissions_';
    const USER_ACTION_PERMISSIONS_CACHE_TTL = 36000;
    const USER_RESOURCE_PERMISSIONS_PREFIX = 'user_resource_permissions_';
    const USER_RESOURCE_PERMISSIONS_CACHE_TTL = 36000;
    const USER_LAST_HEART_BEAT_PREFIX = 'user_last_heart_beat_';
    const USER_HEART_BEAT_GAP = 300; // Seconds, more than this means the user is offline

    protected static $_actionPermissionCache = [];
    protected static $_resourcePermissionCache = [];

    public function doLogin($username, $password)
    {
        $user = DataUser::where('username', $username)->with('group.groupType')->first();

        $event = ZJPHP::createObject('ZJPHP\\Model\\Event\\LoginEvent');
        if (!$user) {
            $event->errMsg = '用户不存在';
            $this->trigger(self::EVENT_LOGIN_FAIL, $event);
        } else {
            $security = ZJPHP::$app->get('security');
            $passwordVerify = $security->validatePassword($password, $user->password);
            if (!$passwordVerify) {
                $event->errMsg = '密码错误';
                $this->trigger(self::EVENT_LOGIN_FAIL, $event);
            } else {
                // Update Secret
                $secret = $security->generateRandomString(64);
                $user->secret = $secret;
                $user->save();

                $event->user = $user;
                $this->trigger(self::EVENT_LOGIN_SUCCESS, $event);
            }
        }
    }

    public function doLogout($userId)
    {
        $user = DataUser::find($userId);
        // Remove secret
        $user->secret = null;
        $user->save();

        $event = ZJPHP::createObject('ZJPHP\\Model\\Event\\LogoutEvent');
        $event->user = $user;

        $this->trigger(self::EVENT_LOGOUT, $event);
    }

    public function validateSecret($secret, $userId)
    {
        return DataUser::where('id', $userId)->where('secret', $secret)->exists();
    }

    public function getActionPermissions($userId)
    {
        $cache = ZJPHP::$app->get('cache');
        $cacheEngine = $cache->engine('files');
        $cacheKey = static::USER_ACTION_PERMISSIONS_PREFIX . $userId;
        $cacheItem = $cacheEngine->getItem($cacheKey);

        if (!empty(static::$_actionPermissionCache)) {
            return static::$_actionPermissionCache;
        } elseif (IS_DEV === false && $cacheItem->get()) {
            static::$_actionPermissionCache = $cacheItem->get();
            return static::$_actionPermissionCache;
        } else {
            $actionPermissions = [];

            $user = DataUser::with('group.actionPermissions', 'authorizedActionPermissions', 'deprivedActionPermissions')->find($userId);

            $userAuthorizedActionPermissions = $user->authorizedActionPermissions;
            $temp1 = [];
            foreach ($userAuthorizedActionPermissions as $actionPermission) {
                array_push($temp1, $actionPermission->hash_identifier);
            }

            $userDeprivedActionPermissions = $user->deprivedActionPermissions;
            $temp2 = [];
            foreach ($userDeprivedActionPermissions as $actionPermission) {
                array_push($temp2, $actionPermission->hash_identifier);
            }

            $groupActionPermissions = $user->group->actionPermissions;
            $temp3 = [];
            foreach ($groupActionPermissions as $actionPermission) {
                array_push($temp3, $actionPermission->hash_identifier);
            }

            $actionPermissions = array_unique(array_diff(array_merge($temp1, $temp3), $temp2));
            
            $cacheItem->set($actionPermissions)->expiresAfter(static::USER_ACTION_PERMISSIONS_CACHE_TTL);
            $cacheEngine->save($cacheItem);

            static::$_actionPermissionCache = $actionPermissions;
            return static::$_actionPermissionCache;
        }
    }

    public function getResourcePermissions($userId)
    {
        $cache = ZJPHP::$app->get('cache');
        $cacheEngine = $cache->engine('files');
        $cacheKey = static::USER_RESOURCE_PERMISSIONS_PREFIX . $userId;
        $cacheItem = $cacheEngine->getItem($cacheKey);

        if (!empty(static::$_resourcePermissionCache)) {
            return static::$_resourcePermissionCache;
        } elseif (IS_DEV === false && $cacheItem->get()) {
            static::$_resourcePermissionCache = $cacheItem->get();
            return static::$_resourcePermissionCache;
        } else {
            $resourcePermissions = [];

            $user = DataUser::with('group.resourcePermissions', 'authorizedResourcePermissions', 'deprivedResourcePermissions')->find($userId);

            $userAuthorizedResourcePermissions = $user->authorizedResourcePermissions;
            $temp1 = [];
            foreach ($userAuthorizedResourcePermissions as $resourcePermission) {
                $temp1[$resourcePermission->hash_identifier] = json_decode($resourcePermission->pivot->rules, true);
            }

            $userDeprivedResourcePermissions = $user->deprivedResourcePermissions;
            $temp2 = [];
            foreach ($userDeprivedResourcePermissions as $resourcePermission) {
                $temp2[$resourcePermission->hash_identifier] = json_decode($resourcePermission->pivot->rules, true);
            }

            $groupResourcePermissions = $user->group->resourcePermissions;
            $temp3 = [];
            foreach ($groupResourcePermissions as $resourcePermission) {
                $temp3[$resourcePermission->hash_identifier] = json_decode($resourcePermission->pivot->rules, true);
            }

            $resourcePermissions = array_diff_key(array_merge($temp3, $temp1), $temp2);
            
            $cacheItem->set($resourcePermissions)->expiresAfter(static::USER_RESOURCE_PERMISSIONS_CACHE_TTL);
            $cacheEngine->save($cacheItem);

            static::$_resourcePermissionCache = $resourcePermissions;
            return static::$_resourcePermissionCache;
        }
    }

    public function checkGroupOwnership($groupId, $groupIdToCheck, $withTrashed = false)
    {
        if ($withTrashed) {
            $groupToCheck = DataGroup::withTrashed()->find($groupIdToCheck);
        } else {
            $groupToCheck = DataGroup::find($groupIdToCheck);
        }

        $groupToCheckGroupLevel = $groupToCheck->level;

        $group = DataGroup::with(
            ['authorization' => function ($query) use ($groupToCheckGroupLevel) {
                $query->where('level', '<=', $groupToCheckGroupLevel);
            }]
        )->find($groupId);
        $groupLevel = $group->level;

        // Check authorization group id
        if (!empty($group->authorization)) {
            if ($group->authorization->contains('authorized_group_id', $groupToCheck->id)) {
                return true;
            }

            for ($i = 0; $i < $groupToCheckGroupLevel; $i++) {
                $parentGroup = $groupToCheck->parent()->first();
                if ($group->authorization->contains('authorized_group_id', $parentGroup->id)) {
                    return true;
                }
            }
        }

        $levelCompareFlag = ($groupLevel == $groupToCheckGroupLevel)
            ? 0
            : ($groupLevel < $groupToCheckGroupLevel ? 1 : -1);
        // Same level
        if ($levelCompareFlag === 0) {
            return $groupIdToCheck == $group->id;
        }
        // Higher level checking
        if ($levelCompareFlag === 1) {
            for ($i = 0; $i < ($groupToCheckGroupLevel - $groupLevel); $i++) {
                if ($withTrashed) {
                    $groupToCheck->load(['parent' => function ($query) {
                        $query->withTrashed();
                    }]);
                    $groupToCheck  = $groupToCheck->parent;
                } else {
                    $groupToCheck = $groupToCheck->parent()->first();
                }
                if ($groupToCheck->id == $group->id) {
                    return true;
                }
            }
        }

        return false;
    }

    public function checkUserOwnership($userId, $groupIdToCheck, $isUserId = false, $withTrashed = false)
    {
        $user = DataUser::find($userId);
        $groupId = $user->group_id;

        if ($isUserId) {
            $userToCheck = DataUser::find($groupIdToCheck);
            $groupIdToCheck = $userToCheck->group_id;
        }

        return $this->checkGroupOwnership($groupId, $groupIdToCheck, $withTrashed);
    }

    public function getGroupOwnership($groupId, $toLevel, $constraints = [], $withTrashed = false)
    {
        $ownership = [];

        $group = DataGroup::find($groupId);

        $authorizations = $group->authorization()->where('level', '<=', $toLevel)->with('authorizedGroup')->get();
        $authorizedGroups = $authorizations->pluck('authorizedGroup')->all();
        // Get authorized ownership
        foreach ($authorizedGroups as $authorizedGroup) {
            if (empty($constraints)) {
                $ownership[] = $authorizedGroup->id;
            } else if (in_array($authorizedGroup->group_type_id, $constraints)) {
                $ownership[] = $authorizedGroup->id;
            }
            if ($authorizedGroup->level < $toLevel) {
                $levelGap = $toLevel - $authorizedGroup->level;
                $key = [];
                $eagearLoadParam = [];
                for ($i = 0; $i < $levelGap; $i++) {
                    $key[] = 'children';
                    $keyStr = implode('.', $key);
                    $eagearLoadParam[$keyStr] = function ($query) use (&$ownership, $constraints, $withTrashed) {
                        $constraintedQuery = clone $query;
                        if (!empty($constraints)) {
                            $constraintedQuery->whereIn('group_type_id', $constraints);
                        }
                        if ($withTrashed) {
                            $constraintedQuery->withTrashed();
                        }
                        $result = $constraintedQuery->get()->pluck('id')->all();
                        $ownership = array_merge($ownership, $result);
                    };
                }
                $authorizedGroup->load($eagearLoadParam);
            }
        }
        // Get relational ownership
        if (empty($constraints)) {
            $ownership[] = $groupId;
        } else if (in_array($group->group_type_id, $constraints)) {
            $ownership[] = $groupId;
        }
        if ($group->level < $toLevel) {
            $levelGap = $toLevel - $group->level;
            $key = [];
            $eagearLoadParam = [];
            for ($i = 0; $i < $levelGap; $i++) {
                $key[] = 'children';
                $keyStr = implode('.', $key);
                $eagearLoadParam[$keyStr] = function ($query) use (&$ownership, $constraints, $withTrashed) {
                    $constraintedQuery = clone $query;
                    if (!empty($constraints)) {
                        $constraintedQuery->whereIn('group_type_id', $constraints);
                    }
                    if ($withTrashed) {
                        $result = $constraintedQuery->withTrashed()->get()->pluck('id')->all();
                    } else {
                        $result = $constraintedQuery->get()->pluck('id')->all();
                    }
                    $ownership = array_merge($ownership, $result);
                };
            }
            $group->load($eagearLoadParam);
        }
        return array_unique($ownership);
    }

    public function addDefaultGroupPermission($event)
    {
        $group = $event->group;
        $group->load('groupType.defaultActionPermissionSetting');

        $defaultActionPermissions = json_decode($group->groupType->defaultActionPermissionSetting->setting, true);

        if (!empty($defaultActionPermissions) && !ArrayHelper::isAssociative($defaultActionPermissions)) {
            $group->actionPermissions()->attach($defaultActionPermissions);
        }
    }

    public function deleteAuthorization($event)
    {
        // TBConfirmed
        if ($event instanceof GroupBatchDeleteEvent) {
            $groups = $event->groups;
            $groups->load(['ownedBy' => function ($query) {
                $query->delete();
            }]);
        } elseif ($event instanceof GroupDeleteEvent) {
            $group = $event->group;
            $group->ownedBy()->delete();
        }
    }
}
