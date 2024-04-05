<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;

use App\Enums\TYPE;
use App\Attributes\ArrayOf;

final class ExampleClass {
    #[ArrayOf(TYPE::string, TYPE::bool)]
    public array $items;
}

final class ArrayOfTest extends TestCase {
    public function testArrayOf(): void {
        $example = new ExampleClass();
        $example->items = ['test', false];
        $rc = new \ReflectionClass($example);
        $props = $rc->getProperties();

        foreach ($props as $prop) {
            $rp = new \ReflectionProperty($example, $prop->getName());
            $attributes = $rp->getAttributes();

            foreach ($attributes as $attribute) {
                $this->assertEquals('App\\Attributes\\ArrayOf', $attribute->getName());
                $args = $attribute->getArguments();
                $this->assertEquals(TYPE::string, $args[0]);
                $this->assertEquals(TYPE::bool, $args[1]);
                $attribute->newInstance()->validate($prop->getValue($example));
            }
        }

    }
}
