<?php

namespace Hans\Alicia\Tests\Core\Models;

    use Hans\Alicia\Tests\Core\Factories\PostFactory;
    use Hans\Alicia\Traits\AliciaHandler;
    use Illuminate\Database\Eloquent\Factories\Factory;
    use Illuminate\Database\Eloquent\Factories\HasFactory;
    use Illuminate\Database\Eloquent\Model;

    class Post extends Model
    {
        use HasFactory;
        use AliciaHandler;

        protected $fillable = [
            'title',
            'content',
        ];

        /**
         * Create a new factory instance for the model.
         *
         * @return Factory<static>
         */
        protected static function newFactory()
        {
            return PostFactory::new();
        }
    }
