<?php

add_filter('bladerunner/controllers/path', function () {
    return get_stylesheet_directory() . '/controllers';
});

/**
 * Template Hierarchy should search for .blade.php files
 */
array_map(function ($type) {
    add_filter("{$type}_template_hierarchy", function ($templates) {
        $result = call_user_func_array('array_merge', array_map(function ($template) {
            $transforms = [
                '%^/?(templates)?/?%' => Bladerunner\Config::repo('bladerunner.disable_option_hack') ? 'templates/' : '',
                '%(\.blade)?(\.php)?$%' => ''
            ];
            $normalizedTemplate = preg_replace(array_keys($transforms), array_values($transforms), $template);

            $path = apply_filters('bladerunner/controllers/path', '/') . "/{$normalizedTemplate}.php";
            if(file_exists($path)) {
                add_filter('bladerunner/controllers/heap', function($heap) use ($path) {
                    if(!in_array($path, $heap)) $heap[] = $path;
                    return $heap;
                });
            }

            return ["{$normalizedTemplate}.blade.php", "{$normalizedTemplate}.php"];
        }, $templates));


        return $result;
    });
}, [
    'index', '404', 'archive', 'author', 'category', 'tag', 'taxonomy', 'date', 'home',
    'frontpage', 'page', 'paged', 'search', 'single', 'singular', 'attachment'
]);

add_filter('template_include', function ($template) {
    $heap = apply_filters('bladerunner/controllers/heap', []);
    if($heap) {
        foreach ($heap as $controllerFile) {
            require_once $controllerFile;
            $class = get_declared_classes();
            $class = '\\' . end($class);
            $controller = new $class();
            if(is_subclass_of($class, "\\Bladerunner\\Controller") && $controller->__getView()) {
                $controller->__setup();
                echo view($controller->__getView(), $controller->__getData());
                return null;
            }
        }

    }
    return $template;
}, PHP_INT_MAX);

//TODO: Make the filter bladerunner/controller/path to array as bladerunner/controller/paths to get use in eg plugins