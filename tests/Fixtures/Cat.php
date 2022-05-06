<?php

namespace Nanigans\SingleTableInheritance\Tests\Fixtures;

class Cat extends Animal {

  protected static $singleTableType = AnimalType::CAT;
}