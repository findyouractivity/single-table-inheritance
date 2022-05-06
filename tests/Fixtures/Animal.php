<?php

namespace Nanigans\SingleTableInheritance\Tests\Fixtures;

use Nanigans\SingleTableInheritance\SingleTableInheritanceTrait;
use Illuminate\Database\Eloquent\Model as Eloquent;

class Animal extends Eloquent {

  use SingleTableInheritanceTrait;

  protected $table = "animals";

  protected static $singleTableTypeField = 'type';

  protected static $singleTableSubclasses = [
    'Nanigans\SingleTableInheritance\Tests\Fixtures\Cat',
    'Nanigans\SingleTableInheritance\Tests\Fixtures\Dog',
  ];
}

enum AnimalType: string {
    case CAT = "cat";
    case DOG = "dog";
}