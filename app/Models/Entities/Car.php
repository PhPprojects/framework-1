<?php


namespace App\Models\Entities;

use Nova\Database\Entity;

class Car extends Entity
{
    public $carid;
    public $make;
    public $model;
    public $costs;
}