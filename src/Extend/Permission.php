<?php namespace Flarum\Extend;

use Illuminate\Contracts\Container\Container;
use Flarum\Core\Models\Permission as PermissionModel;

class Permission implements ExtenderInterface
{
    protected $permission;

    protected $serialize = false;

    protected $grant = [];

    public function __construct($permission)
    {
        $this->permission = $permission;
    }

    public function serialize($serialize = true)
    {
        $this->serialize = $serialize;

        return $this;
    }

    public function grant($callback)
    {
        $this->grant[] = $callback;

        return $this;
    }

    public function extend(Container $container)
    {
        PermissionModel::addPermission($this->permission);

        list($entity, $permission) = explode('.', $this->permission);

        if ($this->serialize) {
            $extender = new SerializeAttributes(
                'Flarum\Api\Serializers\\'.ucfirst($entity).'Serializer',
                function (&$attributes, $model, $serializer) use ($permission) {
                    $attributes['can'.ucfirst($permission)] = (bool) $model->can($serializer->actor->getUser(), $permission);
                }
            );

            $extender->extend($container);
        }

        foreach ($this->grant as $callback) {
            $model = 'Flarum\Core\Models\\'.ucfirst($entity);
            $model::grantPermission($permission, $callback);
        }
    }
}
