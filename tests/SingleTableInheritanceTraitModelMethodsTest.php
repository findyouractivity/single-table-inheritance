<?php

namespace Nanigans\SingleTableInheritance\Tests;

use Illuminate\Support\Facades\DB;
use Nanigans\SingleTableInheritance\Exceptions\SingleTableInheritanceException;
use Nanigans\SingleTableInheritance\Exceptions\SingleTableInheritanceInvalidAttributesException;
use Nanigans\SingleTableInheritance\Tests\Fixtures\Animal;
use Nanigans\SingleTableInheritance\Tests\Fixtures\AnimalType;
use Nanigans\SingleTableInheritance\Tests\Fixtures\Bike;
use Nanigans\SingleTableInheritance\Tests\Fixtures\Car;
use Nanigans\SingleTableInheritance\Tests\Fixtures\MotorVehicle;
use Nanigans\SingleTableInheritance\Tests\Fixtures\Vehicle;

use Nanigans\SingleTableInheritance\Tests\Fixtures\Video;
use Nanigans\SingleTableInheritance\Tests\Fixtures\VideoType;
use Nanigans\SingleTableInheritance\Tests\Fixtures\MP4Video;
use Nanigans\SingleTableInheritance\Tests\Fixtures\VideoTypeEnum;

/**
 * Class SingleTableInheritanceTraitModelMethodsTest
 *
 * A set of tests of the model methods added to by the SingleTableInheritanceTrait
 *
 * @package Nanigans\SingleTableInheritance\Tests
 */
class SingleTableInheritanceTraitModelMethodsTest extends TestCase {

  // getPersistedAttributes

  public function testGetPersistedOfRoot() {
    $attributes = (new Vehicle)->getPersistedAttributes();
    sort($attributes);
    $this->assertEquals(['color', 'created_at', 'id', 'owner_id', 'type', 'updated_at'], $attributes);
  }

  public function testGetPersistedOfChild() {
    $attributes = (new MotorVehicle)->getPersistedAttributes();
    sort($attributes);
    $this->assertEquals(['color', 'created_at', 'fuel', 'id', 'owner_id', 'type', 'updated_at'], $attributes);
  }

  public function testGetPersistedOfLeaf() {
    $attributes = (new Car)->getPersistedAttributes();
    sort($attributes);
    $this->assertEquals(['capacity', 'color', 'created_at', 'fuel', 'id', 'owner_id', 'type', 'updated_at'], $attributes);
  }

  public function testGetPersistedIncludesDates() {
    $vehicle = new Vehicle;
    $vehicle->setDates(['purchase_date']);
    $attributes = $vehicle->getPersistedAttributes();
    sort($attributes);
    $this->assertEquals(['color', 'created_at', 'id', 'owner_id', 'purchase_date', 'type', 'updated_at'], $attributes);
  }

  public function testGetPersistedIncludesPrimaryKey() {
    $vehicle = new Vehicle;
    $vehicle->setPrimaryKey('identifier');
    $attributes = $vehicle->getPersistedAttributes();
    sort($attributes);
    $this->assertEquals(['color', 'created_at', 'identifier', 'owner_id', 'type', 'updated_at'], $attributes);
  }

  public function testGetPersistedIsEmptyIfStaticMappingIsNull() {
    $attributes = Car::withAllPersisted([], function() {
      return (new Car)->getPersistedAttributes();
    });

    $this->assertEquals([], $attributes);
  }

  // getQualifiedSingleTableTypeColumn

  public function testGetQualifiedSingleTableTypeColumn() {
    $car = new Car;
    $car->setTable('cars');
    $tableTypeColumn = Car::withTypeField('discriminator', function() use($car) {
      return $car->getQualifiedSingleTableTypeColumn();
    });
    $this->assertEquals('cars.discriminator', $tableTypeColumn);
  }

  // getSingleTableTypes

  public function testGetSingleTableTypesOfRoot() {
    $types = ['motorvehicle', 'car', 'truck', 'taxi', 'bike'];

    $this->assertEquals($types, (new Vehicle)->getSingleTableTypes());
  }

  public function testGetTypeMapOfChild() {
    $types = ['motorvehicle', 'car', 'truck', 'taxi'];

    $this->assertEquals($types, (new MotorVehicle())->getSingleTableTypes());
  }

  public function testGetTypeMapOfLeaf() {
    $types = ['car'];

    $this->assertEquals($types, (new Car)->getSingleTableTypes());
  }

  // setSingleTableType

  public function testSetSingleTableTypeUsesTypeField() {
    $car = new Car;
    Car::withTypeField('discriminator', function() use($car) {
      $car->setSingleTableType();
    });

    $this->assertEquals('car', $car->getAttributes()['discriminator']);
  }

  public function testSetSingleTableTypeThrowExceptionIfTableTypeIsUnset() {
    $this->expectException(SingleTableInheritanceException::class);

    (new Vehicle)->setSingleTableType();
  }

  // filterPersistedAttributes

  public function testFilterPersistedAttributes() {
    $car = new Car;
    $car->fuel = 'diesel';
    $car->junk = 'trunk';
    $car->wingspan = 30;

    $car->filterPersistedAttributes();

    $this->assertEquals(['fuel' => 'diesel'], $car->getAttributes());
  }

  public function testFilterPersistedAttributesThrowsIfConfigured() {
    $bike = new Bike;
    $bike->fuel = 'diesel';

    $this->expectException(SingleTableInheritanceInvalidAttributesException::class);

    $bike->filterPersistedAttributes();
  }

  public function testFilterPersistedAttributesDoesNothingIfPersistedIsEmpty() {
    $car = \Mockery::mock('Nanigans\SingleTableInheritance\Tests\Fixtures\Car')->makePartial();

    $car->shouldReceive('getPersistedAttributes')
      ->once()
      ->andReturn([]);

    $car->fuel = 'diesel';
    $car->junk = 'trunk';
    $car->wingspan = 30;

    $car->filterPersistedAttributes();

    $this->assertEquals(['fuel' => 'diesel','junk' => 'trunk', 'wingspan' => 30], $car->getAttributes());
  }

  // setFilteredAttributes

  public function testSetFilteredAttributes() {
    $car = new Car;
    $car->setFilteredAttributes(['fuel' => 'diesel','junk' => 'trunk', 'wingspan' => 30]);

    $this->assertEquals(['fuel' => 'diesel'], $car->getAttributes());
  }

  public function testSetFilteredAttributeshrowsIfConfigured() {
    $bike = new Bike;

    $this->expectException(SingleTableInheritanceInvalidAttributesException::class);

    $bike->setFilteredAttributes(['fuel' => 'diesel']);
  }

  public function testSetFilteredAttributesDoesNothingIfPersistedIsEmpty() {
    $car = \Mockery::mock('Nanigans\SingleTableInheritance\Tests\Fixtures\Car')->makePartial();

    $car->shouldReceive('getPersistedAttributes')
      ->once()
      ->andReturn([]);

    $car->setFilteredAttributes(['fuel' => 'diesel','junk' => 'trunk', 'wingspan' => 30]);

    $this->assertEquals(['fuel' => 'diesel','junk' => 'trunk', 'wingspan' => 30], $car->getAttributes());
  }


  // newFromBuilder

  public function testNewFromBuilderWithArray() {
    $vehicle = new Vehicle();
    $newVehicle = $vehicle->newFromBuilder([
      'type' => 'car'
    ]);

    $this->assertInstanceOf('Nanigans\SingleTableInheritance\Tests\Fixtures\Car', $newVehicle);
  }

  public function testNewFromBuilderWithObject() {
    $vehicle = new Vehicle;
    $attr = new \stdClass();
    $attr->type = 'car';

    $newVehicle = $vehicle->newFromBuilder($attr);

    $this->assertInstanceOf('Nanigans\SingleTableInheritance\Tests\Fixtures\Car', $newVehicle);
  }

  public function testNewFromBuilder() {
    $vehicle = new Vehicle;
    $attr = [
      'fuel' => 'diesel',
      'color' => 'red',
      'cruft' => 'junk',
      'type' => 'car'
    ];

    $newVehicle = $vehicle->newFromBuilder($attr);

    $this->assertInstanceOf('Nanigans\SingleTableInheritance\Tests\Fixtures\Car', $newVehicle);
    $this->assertEquals('diesel', $newVehicle->fuel);
    $this->assertEquals('red', $newVehicle->color);
    $this->assertNull($newVehicle->cruft);
  }

  public function testNewFromBuilderWithEnum() {
    $video = new Video;
    $attr = [
      'type' => VideoType::MP4
    ];

    $newVideo = $video->newFromBuilder($attr);

    $this->assertInstanceOf('Nanigans\SingleTableInheritance\Tests\Fixtures\MP4Video', $newVideo);
  }

    public function testNewFromBuilderWithPHPEnum() {
        $animal = new Animal();
        $attr = [
            'type' => AnimalType::CAT->value
        ];

        $newAnimal = $animal->newFromBuilder($attr);

        $this->assertInstanceOf('Nanigans\SingleTableInheritance\Tests\Fixtures\Cat', $newAnimal);
    }
  

  public function testNewFromBuilderDefersToOriginalIfTypeIsUndefined() {
    $vehicle = new Vehicle;
    $fromBuilder = $vehicle->newFromBuilder(['1']);

    $this->assertInstanceOf('Nanigans\SingleTableInheritance\Tests\Fixtures\Vehicle', $fromBuilder);
  }

  public function testNewFromBuilderThrowsIfClassTypeIsUnrecognized() {
    $vehicle = new Vehicle;
    $attr = [
      'type' => 'junk'
    ];

    $this->expectException(SingleTableInheritanceException::class);

    $vehicle->newFromBuilder($attr);
  }
}


