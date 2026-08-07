<?php

namespace App\Repositories\Interfaces;

use App\Models\UserSocialLink;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method UserSocialLink                  create(array<string, mixed> $attributes)
 * @method UserSocialLink|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method UserSocialLink                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method UserSocialLink                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                            save(UserSocialLink $model)
 * @method bool                            update(UserSocialLink $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                            delete(UserSocialLink $model)
 * @method Collection<int, UserSocialLink> all()
 * @method bool                            exists(array<int, string> $columns)
 */
interface UserSocialLinkRepositoryInterface extends BaseRepositoryInterface
{
}
