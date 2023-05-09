<?php

test('PSR4Loader', function () {
    $loader = setupLoader();
    $psr4Classes = ['Class1', 'Class2', 'Namespace1\Class1'];

    foreach ($psr4Classes as $class) {
        expect(class_exists($class))->toBeTrue();
        $classInstance = new $class();
        expect($class)->toEqual($classInstance->testResult());
    }
});

test('PSR0Loader', function () {
    $loader = setupLoader();
    $psr4Classes = ['Test_Class1'];

    foreach ($psr4Classes as $class) {
        expect(class_exists($class))->toBeTrue();
        $classInstance = new $class();
        expect($class)->toEqual($classInstance->testResult());
    }
});

test('classMap', function () {
    $classes = createRandomClasses();
    $loader = setupLoader();

    removeClassMap();
    $totalTime = 0;
    $start = microtime(true);
    for ($i = 0; $i < 100; ++$i) {
        foreach ($classes as $class) {
            if (class_exists($class)) {
                $classInstance = new $class();
                $testResult = $classInstance->testResult();
            }
        }
    }
    $end = microtime(true);
    $totalTime += $end - $start;
    $withoutMap = $totalTime / 10;
    $loader->unRegister();

    $loader = setupLoader(true);
    $totalTime = 0;
    $start = microtime(true);

    for ($i = 0; $i < 100; ++$i) {
        foreach ($classes as $class) {
            if (class_exists($class)) {
                $classInstance = new $class();
                $testResult = $classInstance->testResult();
            }
        }
    }
    $end = microtime(true);
    $totalTime += $end - $start;
    $withMap = $totalTime / 10;
    removeClassMap();
    deleteRandomClasses();
    expect($withMap)->toBeLessThan($withoutMap);
});
