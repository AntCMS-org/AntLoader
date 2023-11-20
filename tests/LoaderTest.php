<?php

test('PSR4Loader', function () {
    $cacheModes = ['none', 'auto', 'filesystem', 'apcu', 'memory'];
    $psr4Classes = ['Class1', 'Class2', 'Namespace1\Class1'];

    foreach ($cacheModes as $mode) {
        setupLoader($mode);

        foreach ($psr4Classes as $class) {
            expect(class_exists($class))->toBeTrue();
            $classInstance = new $class();
            expect($class)->toEqual($classInstance->testResult());
        }
    }
});

test('PSR0Loader', function () {
    $cacheModes = ['none', 'auto', 'filesystem', 'apcu', 'memory'];
    $psr4Classes = ['Test_Class1'];

    foreach ($cacheModes as $mode) {
        setupLoader($mode, 'exampleKey');

        foreach ($psr4Classes as $class) {
            expect(class_exists($class))->toBeTrue();
            $classInstance = new $class();
            expect($class)->toEqual($classInstance->testResult());
        }
    }
});

test('APCUWithKey', function () {
    $psr4Classes = ['Test_Class1'];

    setupLoader('apcu', 'exampleKey');

    foreach ($psr4Classes as $class) {
        expect(class_exists($class))->toBeTrue();
        $classInstance = new $class();
        expect($class)->toEqual($classInstance->testResult());
    }
});

test('FilesystemWithPath', function () {
    $psr4Classes = ['Test_Class1'];
    $path = tempnam(sys_get_temp_dir(), 'classmap') . '.php';

    setupLoader('filesystem', '', $path);

    foreach ($psr4Classes as $class) {
        expect(class_exists($class))->toBeTrue();
        $classInstance = new $class();
        expect($class)->toEqual($classInstance->testResult());
    }
});

test('StopIfNotFound', function () {
    $psr4Classes = ['Does_not_exist'];
    setupLoader('filesystem', '', '', true);

    foreach ($psr4Classes as $class) {
        expect(class_exists($class))->toBeFalse();
    }
});
