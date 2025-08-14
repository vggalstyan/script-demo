<?php

use Interfaces\ValidationAttribute;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionProperty;
use InvalidArgumentException;

final class Validator
{
    /**
     * @var Validator|null
     */
    private static ?Validator $instance = null;

    private function __construct()
    {
    }

    /**
     * @return Validator
     */
    public static function getInstance(): Validator
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * @return bool
     */
    public static function hasInstance(): bool
    {
        return isset(self::$instance);
    }

    private function __clone()
    {
    }

    private function __wakeup()
    {
    }

    /**
     * @param object $dto
     * @return void
     */
    public function validate(object $dto): void
    {
        $reflectionClass = new ReflectionClass($dto);

        foreach ($reflectionClass->getProperties() as $property) {
            $this->validateProperty($dto, $property);
        }
    }

    /**
     *
     * @param object $dto
     * @param ReflectionProperty $property
     * @throws InvalidArgumentException
     */
    private function validateProperty(object $dto, ReflectionProperty $property): void
    {
        $property->setAccessible(true);
        $value = $property->getValue($dto);

        foreach (
            $property->getAttributes(
                ValidationAttribute::class,
                ReflectionAttribute::IS_INSTANCEOF
            ) as $attribute
        ) {
            /** @var ValidationAttribute $attributeInstance */
            $attributeInstance = $attribute->newInstance();

            if (!$attributeInstance->validate($value)) {
                $this->throwValidationException($property, $attributeInstance);
            }
        }
    }

    /**
     *
     * @param ReflectionProperty $property
     * @param ValidationAttribute $attributeInstance
     * @return void
     * @throws InvalidArgumentException
     */
    private function throwValidationException(
        ReflectionProperty $property,
        ValidationAttribute $attributeInstance
    ): void {
        $message = sprintf(
            'Ошибка валидации свойства "%s": %s',
            $property->getName(),
            $attributeInstance->getMessage()
        );

        throw new InvalidArgumentException($message);
    }
}
