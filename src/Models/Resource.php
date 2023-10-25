<?php

namespace Hans\Alicia\Models;

use Hans\Alicia\Facades\Signature;
use Hans\Alicia\Traits\FFMpegPreConfig;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Fillables:.
 *
 * @property int    $id
 * @property string $title
 * @property string $directory
 * @property string $file
 * @property string $hls
 * @property string $link
 * @property string $extension
 * @property array  $options
 * @property bool   $external
 *
 * Attributes:
 * @property string $downloadUrl
 * @property string $streamUrl
 * @property string $path
 * @property string $fullPath
 *
 * Foreign keys:
 * @property int $parent_id
 *
 * @mixin Model
 */
class Resource extends Model
{
    // TODO: model attributes is not documented
    use FFMpegPreConfig;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'title',
        'directory',
        'file',
        'hls',
        'link',
        'extension',
        'options',
        'external',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'options'  => 'array',
        'external' => 'boolean',
    ];

    /**
     * Define downloadUrl accessor.
     *
     * @return Attribute
     */
    public function downloadUrl(): Attribute
    {
        return new Attribute(
            get: function () {
                if (alicia_config('signed')) {
                    return URL::temporarySignedRoute(
                        'alicia.download',
                        now()->addMinutes(alicia_config('expiration', 30)),
                        [
                            'resource' => $this->id,
                            'hash'     => Signature::create(),
                        ]
                    );
                } else {
                    return route('alicia.download', ['resource' => $this->id]);
                }
            }
        );
    }

    /**
     * Define streamUrl accessor.
     *
     * @return Attribute
     */
    public function streamUrl(): Attribute
    {
        return new Attribute(
            get: function () {
                if (in_array($this->extension, alicia_config('extensions.audios'))) {
                    $response = new BinaryFileResponse(alicia_storage()->path($this->path));
                    BinaryFileResponse::trustXSendfileTypeHeader();

                    return $response;
                }

                if (alicia_config('hls.enable') and $this->hls) {
                    return url('resources/'.$this->directory.'/'.$this->hls);
                } else {
                    return url('resources/'.$this->directory.'/'.$this->file);
                }
            }
        );
    }

    /**
     * Define path accessor.
     *
     * @return Attribute
     */
    public function path(): Attribute
    {
        return new Attribute(get: fn () => $this->directory.'/'.$this->file);
    }

    /**
     * Define fullPath accessor.
     *
     * @return Attribute
     */
    public function fullPath(): Attribute
    {
        return new Attribute(get: fn () => alicia_storage()->path($this->path));
    }

    /**
     * Determine resource is external.
     *
     * @return bool
     */
    public function isExternal(): bool
    {
        return $this->external;
    }

    /**
     * Determine resource is not external.
     *
     * @return bool
     */
    public function isNotExternal(): bool
    {
        return !$this->isExternal();
    }

    /**
     * Update options attributes.
     *
     * @param array $options
     *
     * @return bool
     */
    public function updateOptions(array $options): bool
    {
        return $this->update(['options' => array_merge($this->getOptions(), $options)]);
    }

    /**
     * Return options.
     *
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Definition of parent relationship.
     *
     * @return BelongsTo
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * Definition of children relationship.
     *
     * @return HasMany
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }
}
