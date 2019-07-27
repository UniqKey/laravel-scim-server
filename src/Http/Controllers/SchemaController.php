<?php

namespace UniqKey\Laravel\SCIMServer\Http\Controllers;

use UniqKey\Laravel\SCIMServer\SCIMRoutes;
use Tmilos\ScimSchema\Builder\SchemaBuilderV2;
use UniqKey\Laravel\SCIMServer\SCIM\ListResponse;
use UniqKey\Laravel\SCIMServer\Exceptions\SCIMException;
use UniqKey\Laravel\SCIMServer\SCIMConfig;

class SchemaController extends BaseController
{
    protected $schemas = null;

    public function getSchemas()
    {
        if (null !== $this->schemas) {
            return $this->schemas;
        }

        $config = resolve(SCIMConfig::class)->getConfig();
        $schemas = [];

        foreach ($config as $key => $value) {
            if ($key != 'Users' && $key != 'Group') {
                continue;
            }

            // TODO: FIX THIS. Schema is now an array but should be a string
            $schema = (new SchemaBuilderV2())->get($value['schema'][0]);

            if ($schema == null) {
                throw (new SCIMException('Schema not found'))
                    ->setHttpCode(404);
            }

            $schema->getMeta()->setLocation(
                resolve(SCIMRoutes::class)->route('scim.schema', [
                    'id' =>  $schema->id,
                ])
            );

            $schemas[] = $schema->serializeObject();
        }

        $this->schemas = collect($schemas);

        return $this->schemas;
    }

    public function show($id)
    {
        $schemas = $this->getSchemas();

        $result = $schemas->first(function ($value, $key) use ($id) {
            return $value['id'] == $id;
        });

        if (null === $result) {
            throw (new SCIMException("Resource \"{$id}\" not found"))
                ->setHttpCode(404);
        }

        return $result;
    }

    public function index()
    {
        return new ListResponse($this->getSchemas(), 1, $this->getSchemas()->count());
    }
}
