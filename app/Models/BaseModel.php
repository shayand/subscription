<?php


namespace App\Models;

use Exception;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BaseModel extends Model
{
    use HasFactory;

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['created_at', 'updated_at'];

    /**
     * Get created_at in jalali
     *
     * @param string $value
     * @return string
     */
//    public function getCreatedAtAttribute(string $value): string
//    {
//        return Carbon::parse($value)->timestamp >= 0 ? jdate($value)->format('Y-m-d H:i:s') : 'N/A';
//    }

    /**
     * Get updated_at in jalali
     *
     * @param string $value
     * @return string
     */
//    public function getUpdatedAtAttribute(string $value): string
//    {
//        return Carbon::parse($value)->timestamp >= 0 ? jdate($value)->format('Y-m-d H:i:s') : 'N/A';
//    }

    /**
     * Get a resource or return non-existence instance
     *
     * @param int $id
     * @param array $columns
     * @return BaseModel
     * @throws Exception
     */
    public function findResource(int $id, array $columns = ['*']): BaseModel
    {
        try {
            return $this->find($id, $columns) ?? $this;

        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Get a resource or return non-existence instance
     *
     * @param string $field
     * @param string|int|float $value
     * @param array $columns
     * @return BaseModel
     * @throws Exception
     */
    public function findResourceByColumn(string $field, $value, array $columns = ['*']): BaseModel
    {
        try {
            return $this->where($field, $value)->first($columns) ?? $this;

        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Delete a resource by a column
     *
     * @param string $field
     * @param $value
     * @throws Exception
     */
    public function deleteResourceByColumn(string $field, $value): void
    {
        try {
            $this->where($field, '=', $value)->delete();

        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Get Resources with relations
     *
     * @param array $modelColumns store list of columns to be selected on model
     * @param array $modelRestrictions store list of conditions on model
     * @param array $relations store list of relations to model
     * @return Collection
     * @throws Exception
     */
    public function getResources(array $modelColumns = ['*'], array $modelRestrictions = [], array $relations = []): Collection
    {
        try {
            return !$this->exists
                ? $this->modelResources($modelColumns, $modelRestrictions, $relations)->get()
                : $this->modelResources($modelColumns, $modelRestrictions, $relations)
                    ->where($this->getTable() . '.id', '=', $this->getAttribute('id'))
                    ->get();

        } catch (Exception $e) {
            dd($e->getMessage());
            throw $e;
        }
    }

    /**
     * Gt resources with relations
     *
     * @param array|string[] $resourceColumns
     * @param array $relationColumns
     * @return Collection
     * @throws Exception
     */
    public function getActiveResources(array $resourceColumns = ['*'], array $relationColumns = []): Collection
    {
        try {
            return $this->active()->get($resourceColumns);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Get model count based on model restrictions
     *
     * @param array $modelRestriction
     * @return int
     * @throws Exception
     */
    public function getResourcesCount(array $modelRestriction): int
    {
        try {
            return $this->getResources(['*'], $modelRestriction)->count();
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Provide model resources
     *
     * @param array $modelColumns
     * @param array $modelRestrictions
     * @param array $relations
     * @return Builder
     * @throws Exception
     */
    public function modelResources(array $modelColumns = ['*'], array $modelRestrictions = [], array $relations = []): Builder
    {
        try {
            return $this->getModelRestrictions($modelRestrictions, $relations)->select($modelColumns);

        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Save new resource
     *
     * @param array $request
     * @return self
     * @throws Exception
     */
    public function storeModel(array $request): self
    {
        try {
            $this->setAttributes($request)->save();
            return $this;

        } catch (QueryException $e) {
            throw $e;
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Update an existing resource
     *
     * @param array $request
     * @return self
     * @throws Exception
     */
    public function updateModel(array $request): self
    {
        try {
            $this->setAttributes($request)->save();
            return $this;

        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Update an existing resource
     *
     * @return bool|null
     * @throws Exception
     */
    public function deleteModel(): ?bool
    {
        try {
            return $this->delete();

        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Delete bulk from model
     *
     * @param array $ids
     * @return bool|null
     * @throws Exception
     */
    public function bulkDeleteModel(array $ids): ?bool
    {
        try {
            return $this->whereIn('id', $ids)->delete();

        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Set attributes of model
     *
     * @param array $request
     * @return $this|Model
     */
    public function setAttributes(array $request): Model
    {
        foreach ($this->fillable as $item) {
            $this->setAttribute($item, $request[$item] ?? null);
        }

        return $this;
    }

    /**
     * Set multi record attributes of model
     *
     * @param array $request
     * @return array
     */
    public function setMultipleRecordAttributes(array $request): array
    {
        // this function should implement based on function requirement
        return $request;
    }

    /**
     * Get model with its related columns
     *
     * @param array $modelRestrictions
     * @param array $relationColumns
     * @return Builder
     * @throws Exception
     */
    public function getModelRestrictions(array $modelRestrictions, array $relationColumns): Builder
    {
        try {
            $builder = collect($relationColumns)->map(function ($columns, $related) {
                return [$related => function ($q) use ($columns) {
                    /**@var Builder $q */
                    $this->buildWhereClause($q, $columns);
                    $this->buildLimitClause($q, $columns);
                    $this->buildOrderClause($q, $columns);
                    $this->buildGroupByClause($q, $columns);
                    $this->buildAggregateClause($q, $columns);
                    $q->addSelect($columns);
                }];
            });

            $relations = [];
            foreach ($builder->all() as $closures) {
                foreach ((array)$closures as $key => $closure) {
                    $relations[$key] = $closure;
                }
            }

            $eager = self::with($relations);

            if (!empty($modelRestrictions)) {
                $this->buildWhereClause($eager, $modelRestrictions);
                $this->buildLimitClause($eager, $modelRestrictions);
                $this->buildOrderClause($eager, $modelRestrictions);
            }

            return $eager;

        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * build where clauses
     *
     * @param Builder $q
     * @param array $columns
     * @return void
     */
    private function buildWhereClause($q, array &$columns): void
    {
        if (isset($columns['wheres'])) {
            foreach ((array)$columns['wheres'] as $where) {
                $type = $where['type'] ?? 'operator';
                $column = trim($where['column']);
                $sign = trim($where['sign'] ?? '=');
                $value = is_array($where['value']) ? array_map('trim', array_values($where['value'])) : trim($where['value']);

                $type === 'in' && $q->whereIn($column, $value);
                $type === 'operator' && $q->where($column, $sign, $value);
                $type === 'between' && $q->whereBetween($column, $value);
                $type === 'raw' && $q->whereRaw($where['sql'], $where['bindings']);
            }
            unset($columns['wheres']);
        }
    }

    /**
     * build limit clause
     *
     * @param Builder $q
     * @param array $columns
     * @return void
     */
    private function buildLimitClause($q, array &$columns): void
    {
        if (isset($columns['limit'])) {
            $q->limit((int)$columns['limit']['limit']);

            # Add Offset to query
            isset($columns['limit']['offset']) && $q->offset((int)$columns['limit']['offset']);

            unset($columns['limit']);
        }
    }

    /**
     * build order clause
     *
     * @param Builder $q
     * @param array $columns
     * @return void
     */
    private function buildOrderClause($q, array &$columns): void
    {
        # Add orders to query
        if (isset($columns['orders'])) {
            foreach ((array)$columns['orders'] as $order) {
                $q->orderBy($order['column'], $order['direction'] ?? 'asc');
            }
            unset($columns['orders']);
        }
    }

    /**
     * build group by clause
     *
     * @param Builder $q
     * @param array $columns
     * @return void
     */
    private function buildGroupByClause($q, array &$columns): void
    {
        if (isset($columns['group'])) {
            $q->groupBy($columns['group']['columns']);
            unset($columns['group']);
        }
    }

    /**
     * build aggregation clause
     *
     * @param Builder $q
     * @param array $columns
     * @return void
     */
    private function buildAggregateClause($q, array &$columns): void
    {
        if (isset($columns['aggregates'])) {
            foreach ((array)$columns['aggregates'] as $key => $aggregate) {
                $as = $aggregate['as'] ?? "aggregate$key";
                $q->selectRaw("{$aggregate['function']}(`{$aggregate['column']}`) as $as");

                unset($columns['aggregates']);
            }
        }
    }
}
