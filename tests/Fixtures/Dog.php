<?php

namespace Nanigans\SingleTableInheritance\Tests\Fixtures;

class Dog extends Animal {

  protected static $singleTableType = AnimalType::DOG;
}