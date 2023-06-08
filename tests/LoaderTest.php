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
    // Prepare test data
    deleteRandomClasses();
    $classes = createRandomClasses(1000);
    $loader = setupLoader();

    // Test class loading without class map
    $loader->resetClassMap();
    $start = microtime(true);
    foreach ($classes as $class) {
        expect(class_exists($class))->toBeTrue();
        $classInstance = new $class();
        expect($class)->toEqual($classInstance->testResult());
    }
    $end = microtime(true);
    $totalTime = $end - $start;
    $withoutMap = $totalTime / 10;
    $loader->unRegister();

    // Test class loading with class map
    deleteRandomClasses();
    $classes = createRandomClasses(1000);

    $loader = setupLoader(true);
    $loader->resetClassMap(); // Ensure we don't have an old class map
    $loader->checkClassMap();

    $start = microtime(true);
    foreach ($classes as $class) {
        expect(class_exists($class))->toBeTrue();
        $classInstance = new $class();
        expect($class)->toEqual($classInstance->testResult());
    }
    $end = microtime(true);
    $totalTime = $end - $start;
    $withMap = $totalTime / 10;

    // Clean up and output result
    $loader->resetClassMap();
    deleteRandomClasses();
    expect($withMap)->toBeLessThan($withoutMap);
});
