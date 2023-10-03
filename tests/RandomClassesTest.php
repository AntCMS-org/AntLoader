<?php

test('Classmap with APCU', function () {
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

    $loader = setupLoader('apcu');
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

test('Classmap with filesystem', function () {
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

    $loader = setupLoader('filesystem');
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

test('Classmap with filesystem updating classmap', function () {
    // Test class loading with class map
    deleteRandomClasses();
    $classes = createRandomClasses(10);

    $loader = setupLoader('filesystem');
    $loader->resetClassMap();
    $loader->checkClassMap();

    $moreClasses = createRandomClasses(10);
    $classes = array_merge($classes, $moreClasses);

    foreach ($classes as $class) {
        expect(class_exists($class))->toBeTrue();
        $classInstance = new $class();
        expect($class)->toEqual($classInstance->testResult());
    }

    // Clean up and output result
    $loader->resetClassMap();
    deleteRandomClasses();
});

test('Classmap with APCu updating classmap', function () {
    // Test class loading with class map
    deleteRandomClasses();
    $classes = createRandomClasses(10);

    $loader = setupLoader('apcu');
    $loader->resetClassMap();
    $loader->checkClassMap();

    $moreClasses = createRandomClasses(10);
    $classes = array_merge($classes, $moreClasses);

    foreach ($classes as $class) {
        expect(class_exists($class))->toBeTrue();
        $classInstance = new $class();
        expect($class)->toEqual($classInstance->testResult());
    }

    // Clean up and output result
    $loader->resetClassMap();
    deleteRandomClasses();
});
