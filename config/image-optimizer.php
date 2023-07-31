<?php

    use Spatie\ImageOptimizer\Optimizers\Cwebp;
    use Spatie\ImageOptimizer\Optimizers\Jpegoptim;
    use Spatie\ImageOptimizer\Optimizers\Optipng;

    return [
        /*
         * When calling `optimize` the package will automatically determine which optimizers
         * should run for the given image.
         */
        'optimizers'             => [

            Jpegoptim::class => [
                '-m85', // set maximum quality to 85%
                '--strip-all',  // this strips out all text information such as comments and EXIF data
                '--all-progressive',  // this will make sure the resulting image is a progressive one
            ],

            Optipng::class => [
                '-i0', // this will result in a non-interlaced, progressive scanned image
                '-o2',  // this set the optimization level to two (multiple IDAT compression trials)
                '-quiet', // required parameter for this package
            ],

            Cwebp::class => [
                '-m 6', // for the slowest compression method in order to get the best compression.
                '-pass 10', // for maximizing the amount of analysis pass.
                '-mt', // multithreading for some speed improvements.
                '-q 90', // quality factor that brings the least noticeable changes.
            ],
        ],

        /*
        * The directory where your binaries are stored.
        * Only use this when you binaries are not accessible in the global environment.
        */
        'binary_path'            => '',

        /*
         * The maximum time in seconds each optimizer is allowed to run separately.
         */
        'timeout'                => 60,

        /*
         * If set to `true` all output of the optimizer binaries will be appended to the default log.
         * You can also set this to a class that implements `Psr\Log\LoggerInterface`.
         */
        'log_optimizer_activity' => true,
    ];
