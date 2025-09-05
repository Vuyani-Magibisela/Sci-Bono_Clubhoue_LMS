<?php
/**
 * Query Builder for complex database queries
 * Phase 5 Implementation
 */

class QueryBuilder {
    private $conn;
    private $table;
    private $select = ['*'];
    private $joins = [];
    private $wheres = [];
    private $orderBy = [];
    private $groupBy = [];
    private $having = [];
    private $limit;
    private $offset;
    private $bindings = [];
    
    public function __construct($conn, $table = null) {
        $this->conn = $conn;
        $this->table = $table;
    }
    
    /**
     * Set table for query
     */
    public function table($table) {
        $this->table = $table;
        return $this;
    }
    
    /**
     * Set select columns
     */
    public function select($columns = ['*']) {
        $this->select = is_array($columns) ? $columns : func_get_args();
        return $this;
    }
    
    /**
     * Add select raw expression
     */
    public function selectRaw($expression) {
        $this->select[] = $expression;
        return $this;
    }
    
    /**
     * Add where condition
     */
    public function where($column, $operator = '=', $value = null) {
        // Handle single parameter (column = value)
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }
        
        $this->wheres[] = [
            'type' => 'basic',
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
            'boolean' => 'AND'
        ];
        
        $this->bindings[] = $value;
        
        return $this;
    }
    
    /**
     * Add OR where condition
     */
    public function orWhere($column, $operator = '=', $value = null) {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }
        
        $this->wheres[] = [
            'type' => 'basic',
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
            'boolean' => 'OR'
        ];
        
        $this->bindings[] = $value;
        
        return $this;
    }
    
    /**
     * Add where raw condition
     */
    public function whereRaw($sql, $bindings = []) {
        $this->wheres[] = [
            'type' => 'raw',
            'sql' => $sql,
            'boolean' => 'AND'
        ];
        
        foreach ($bindings as $binding) {
            $this->bindings[] = $binding;
        }
        
        return $this;
    }
    
    /**
     * Add where in condition
     */
    public function whereIn($column, $values) {
        $this->wheres[] = [
            'type' => 'in',
            'column' => $column,
            'values' => $values,
            'boolean' => 'AND'
        ];
        
        foreach ($values as $value) {
            $this->bindings[] = $value;
        }
        
        return $this;
    }
    
    /**
     * Add where not in condition
     */
    public function whereNotIn($column, $values) {
        $this->wheres[] = [
            'type' => 'not_in',
            'column' => $column,
            'values' => $values,
            'boolean' => 'AND'
        ];
        
        foreach ($values as $value) {
            $this->bindings[] = $value;
        }
        
        return $this;
    }
    
    /**
     * Add where null condition
     */
    public function whereNull($column) {
        $this->wheres[] = [
            'type' => 'null',
            'column' => $column,
            'boolean' => 'AND'
        ];
        
        return $this;
    }
    
    /**
     * Add where not null condition
     */
    public function whereNotNull($column) {
        $this->wheres[] = [
            'type' => 'not_null',
            'column' => $column,
            'boolean' => 'AND'
        ];
        
        return $this;
    }
    
    /**
     * Add where between condition
     */
    public function whereBetween($column, $values) {
        $this->wheres[] = [
            'type' => 'between',
            'column' => $column,
            'values' => $values,
            'boolean' => 'AND'
        ];
        
        $this->bindings[] = $values[0];
        $this->bindings[] = $values[1];
        
        return $this;
    }
    
    /**
     * Add where not between condition
     */
    public function whereNotBetween($column, $values) {
        $this->wheres[] = [
            'type' => 'not_between',
            'column' => $column,
            'values' => $values,
            'boolean' => 'AND'
        ];
        
        $this->bindings[] = $values[0];
        $this->bindings[] = $values[1];
        
        return $this;
    }
    
    /**
     * Add where like condition
     */
    public function whereLike($column, $value) {
        return $this->where($column, 'LIKE', $value);
    }
    
    /**
     * Add where date condition
     */
    public function whereDate($column, $operator, $value = null) {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }
        
        $this->wheres[] = [
            'type' => 'date',
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
            'boolean' => 'AND'
        ];
        
        $this->bindings[] = $value;
        
        return $this;
    }
    
    /**
     * Add where exists subquery
     */
    public function whereExists($callback) {
        $query = new QueryBuilder($this->conn);
        $callback($query);
        
        $this->wheres[] = [
            'type' => 'exists',
            'query' => $query,
            'boolean' => 'AND'
        ];
        
        // Add subquery bindings
        foreach ($query->getBindings() as $binding) {
            $this->bindings[] = $binding;
        }
        
        return $this;
    }
    
    /**
     * Add join
     */
    public function join($table, $first, $operator = '=', $second = null, $type = 'inner') {
        $this->joins[] = [
            'type' => $type,
            'table' => $table,
            'first' => $first,
            'operator' => $operator,
            'second' => $second
        ];
        
        return $this;
    }
    
    /**
     * Add left join
     */
    public function leftJoin($table, $first, $operator = '=', $second = null) {
        return $this->join($table, $first, $operator, $second, 'left');
    }
    
    /**
     * Add right join
     */
    public function rightJoin($table, $first, $operator = '=', $second = null) {
        return $this->join($table, $first, $operator, $second, 'right');
    }
    
    /**
     * Add inner join
     */
    public function innerJoin($table, $first, $operator = '=', $second = null) {
        return $this->join($table, $first, $operator, $second, 'inner');
    }
    
    /**
     * Add cross join
     */
    public function crossJoin($table) {
        $this->joins[] = [
            'type' => 'cross',
            'table' => $table
        ];
        
        return $this;
    }
    
    /**
     * Add order by
     */
    public function orderBy($column, $direction = 'ASC') {
        $this->orderBy[] = [
            'column' => $column,
            'direction' => strtoupper($direction)
        ];
        
        return $this;
    }
    
    /**
     * Add order by desc
     */
    public function orderByDesc($column) {
        return $this->orderBy($column, 'DESC');
    }
    
    /**
     * Add order by raw
     */
    public function orderByRaw($sql) {
        $this->orderBy[] = [
            'raw' => $sql
        ];
        
        return $this;
    }
    
    /**
     * Add random order
     */
    public function inRandomOrder() {
        return $this->orderByRaw('RAND()');
    }
    
    /**
     * Add group by
     */
    public function groupBy($columns) {
        $this->groupBy = array_merge($this->groupBy, is_array($columns) ? $columns : func_get_args());
        return $this;
    }
    
    /**
     * Add having condition
     */
    public function having($column, $operator = '=', $value = null) {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }
        
        $this->having[] = [
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
            'boolean' => 'AND'
        ];
        
        $this->bindings[] = $value;
        
        return $this;
    }
    
    /**
     * Add having raw condition
     */
    public function havingRaw($sql, $bindings = []) {
        $this->having[] = [
            'raw' => $sql,
            'boolean' => 'AND'
        ];
        
        foreach ($bindings as $binding) {
            $this->bindings[] = $binding;
        }
        
        return $this;
    }
    
    /**
     * Set limit
     */
    public function limit($count) {
        $this->limit = $count;
        return $this;
    }
    
    /**
     * Set offset
     */
    public function offset($count) {
        $this->offset = $count;
        return $this;
    }
    
    /**
     * Take (alias for limit)
     */
    public function take($count) {
        return $this->limit($count);
    }
    
    /**
     * Skip (alias for offset)
     */
    public function skip($count) {
        return $this->offset($count);
    }
    
    /**
     * Add pagination
     */
    public function paginate($perPage, $page = 1) {
        $offset = ($page - 1) * $perPage;
        return $this->limit($perPage)->offset($offset);
    }
    
    /**
     * Get results
     */
    public function get() {
        $sql = $this->toSQL();
        $stmt = $this->conn->prepare($sql);
        
        if (!empty($this->bindings)) {
            $types = $this->getBindingTypes();
            $stmt->bind_param($types, ...$this->bindings);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Get first result
     */
    public function first() {
        $results = $this->limit(1)->get();
        return $results[0] ?? null;
    }
    
    /**
     * Find by primary key
     */
    public function find($id, $key = 'id') {
        return $this->where($key, $id)->first();
    }
    
    /**
     * Count results
     */
    public function count($column = '*') {
        // Create a copy for count query
        $countQuery = clone $this;
        $countQuery->select = ["COUNT({$column}) as count"];
        $countQuery->orderBy = [];
        $countQuery->limit = null;
        $countQuery->offset = null;
        
        $result = $countQuery->first();
        return (int) ($result['count'] ?? 0);
    }
    
    /**
     * Get max value
     */
    public function max($column) {
        $query = clone $this;
        $query->select = ["MAX({$column}) as max_value"];
        $query->orderBy = [];
        $query->limit = null;
        $query->offset = null;
        
        $result = $query->first();
        return $result['max_value'] ?? null;
    }
    
    /**
     * Get min value
     */
    public function min($column) {
        $query = clone $this;
        $query->select = ["MIN({$column}) as min_value"];
        $query->orderBy = [];
        $query->limit = null;
        $query->offset = null;
        
        $result = $query->first();
        return $result['min_value'] ?? null;
    }
    
    /**
     * Get average value
     */
    public function avg($column) {
        $query = clone $this;
        $query->select = ["AVG({$column}) as avg_value"];
        $query->orderBy = [];
        $query->limit = null;
        $query->offset = null;
        
        $result = $query->first();
        return $result['avg_value'] ?? null;
    }
    
    /**
     * Get sum value
     */
    public function sum($column) {
        $query = clone $this;
        $query->select = ["SUM({$column}) as sum_value"];
        $query->orderBy = [];
        $query->limit = null;
        $query->offset = null;
        
        $result = $query->first();
        return $result['sum_value'] ?? null;
    }
    
    /**
     * Check if any results exist
     */
    public function exists() {
        return $this->count() > 0;
    }
    
    /**
     * Insert data
     */
    public function insert($data) {
        if (empty($data)) {
            return false;
        }
        
        // Handle array of arrays (multiple inserts)
        if (is_array(reset($data))) {
            return $this->insertMultiple($data);
        }
        
        $columns = array_keys($data);
        $placeholders = array_fill(0, count($columns), '?');
        
        $sql = "INSERT INTO `{$this->table}` (`" . implode('`, `', $columns) . "`) VALUES (" . implode(', ', $placeholders) . ")";
        
        $stmt = $this->conn->prepare($sql);
        
        $types = "";
        $values = [];
        foreach ($data as $value) {
            $types .= $this->getValueType($value);
            $values[] = $value;
        }
        
        $stmt->bind_param($types, ...$values);
        $result = $stmt->execute();
        
        return $result ? $this->conn->insert_id : false;
    }
    
    /**
     * Insert multiple records
     */
    public function insertMultiple($data) {
        if (empty($data)) {
            return false;
        }
        
        $columns = array_keys(reset($data));
        $placeholderRows = [];
        $values = [];
        $types = "";
        
        foreach ($data as $row) {
            $placeholders = array_fill(0, count($columns), '?');
            $placeholderRows[] = '(' . implode(', ', $placeholders) . ')';
            
            foreach ($row as $value) {
                $types .= $this->getValueType($value);
                $values[] = $value;
            }
        }
        
        $sql = "INSERT INTO `{$this->table}` (`" . implode('`, `', $columns) . "`) VALUES " . implode(', ', $placeholderRows);
        
        $stmt = $this->conn->prepare($sql);
        
        if (!empty($values)) {
            $stmt->bind_param($types, ...$values);
        }
        
        return $stmt->execute();
    }
    
    /**
     * Update data
     */
    public function update($data) {
        if (empty($data)) {
            return false;
        }
        
        $setParts = [];
        $values = [];
        $types = "";
        
        foreach ($data as $column => $value) {
            $setParts[] = "`{$column}` = ?";
            $values[] = $value;
            $types .= $this->getValueType($value);
        }
        
        // Add where bindings
        foreach ($this->bindings as $binding) {
            $values[] = $binding;
            $types .= $this->getValueType($binding);
        }
        
        $sql = "UPDATE `{$this->table}` SET " . implode(', ', $setParts) . $this->compileWheres();
        
        $stmt = $this->conn->prepare($sql);
        
        if (!empty($values)) {
            $stmt->bind_param($types, ...$values);
        }
        
        return $stmt->execute();
    }
    
    /**
     * Update or insert data
     */
    public function updateOrInsert($attributes, $values = []) {
        $record = $this->select('*');
        
        foreach ($attributes as $key => $value) {
            $record->where($key, $value);
        }
        
        if ($record->exists()) {
            return $record->update($values);
        }
        
        return $this->insert(array_merge($attributes, $values));
    }
    
    /**
     * Delete records
     */
    public function delete() {
        $sql = "DELETE FROM `{$this->table}`" . $this->compileWheres();
        
        $stmt = $this->conn->prepare($sql);
        
        if (!empty($this->bindings)) {
            $types = $this->getBindingTypes();
            $stmt->bind_param($types, ...$this->bindings);
        }
        
        return $stmt->execute();
    }
    
    /**
     * Truncate table
     */
    public function truncate() {
        $sql = "TRUNCATE TABLE `{$this->table}`";
        return $this->conn->query($sql);
    }
    
    /**
     * Convert to SQL string
     */
    public function toSQL() {
        $sql = "SELECT " . implode(', ', $this->select) . " FROM `{$this->table}`";
        
        // Add joins
        $sql .= $this->compileJoins();
        
        // Add where conditions
        $sql .= $this->compileWheres();
        
        // Add group by
        if (!empty($this->groupBy)) {
            $sql .= " GROUP BY " . implode(', ', $this->groupBy);
        }
        
        // Add having
        if (!empty($this->having)) {
            $sql .= $this->compileHaving();
        }
        
        // Add order by
        if (!empty($this->orderBy)) {
            $orderParts = [];
            foreach ($this->orderBy as $order) {
                if (isset($order['raw'])) {
                    $orderParts[] = $order['raw'];
                } else {
                    $orderParts[] = $order['column'] . ' ' . $order['direction'];
                }
            }
            $sql .= " ORDER BY " . implode(', ', $orderParts);
        }
        
        // Add limit and offset
        if ($this->limit !== null) {
            $sql .= " LIMIT " . $this->limit;
            
            if ($this->offset !== null) {
                $sql .= " OFFSET " . $this->offset;
            }
        }
        
        return $sql;
    }
    
    /**
     * Get current bindings
     */
    public function getBindings() {
        return $this->bindings;
    }
    
    /**
     * Compile joins
     */
    private function compileJoins() {
        $sql = '';
        
        foreach ($this->joins as $join) {
            $type = strtoupper($join['type']);
            
            if ($join['type'] === 'cross') {
                $sql .= " CROSS JOIN `{$join['table']}`";
            } else {
                $sql .= " {$type} JOIN `{$join['table']}` ON {$join['first']} {$join['operator']} {$join['second']}";
            }
        }
        
        return $sql;
    }
    
    /**
     * Compile where conditions
     */
    private function compileWheres() {
        if (empty($this->wheres)) {
            return '';
        }
        
        $sql = ' WHERE ';
        $conditions = [];
        
        foreach ($this->wheres as $i => $where) {
            $boolean = $i === 0 ? '' : " {$where['boolean']} ";
            
            switch ($where['type']) {
                case 'basic':
                    $conditions[] = $boolean . "{$where['column']} {$where['operator']} ?";
                    break;
                    
                case 'raw':
                    $conditions[] = $boolean . $where['sql'];
                    break;
                    
                case 'in':
                    $placeholders = str_repeat('?,', count($where['values']) - 1) . '?';
                    $conditions[] = $boolean . "{$where['column']} IN ({$placeholders})";
                    break;
                    
                case 'not_in':
                    $placeholders = str_repeat('?,', count($where['values']) - 1) . '?';
                    $conditions[] = $boolean . "{$where['column']} NOT IN ({$placeholders})";
                    break;
                    
                case 'null':
                    $conditions[] = $boolean . "{$where['column']} IS NULL";
                    break;
                    
                case 'not_null':
                    $conditions[] = $boolean . "{$where['column']} IS NOT NULL";
                    break;
                    
                case 'between':
                    $conditions[] = $boolean . "{$where['column']} BETWEEN ? AND ?";
                    break;
                    
                case 'not_between':
                    $conditions[] = $boolean . "{$where['column']} NOT BETWEEN ? AND ?";
                    break;
                    
                case 'date':
                    $conditions[] = $boolean . "DATE({$where['column']}) {$where['operator']} ?";
                    break;
                    
                case 'exists':
                    $conditions[] = $boolean . "EXISTS ({$where['query']->toSQL()})";
                    break;
            }
        }
        
        return $sql . implode('', $conditions);
    }
    
    /**
     * Compile having conditions
     */
    private function compileHaving() {
        if (empty($this->having)) {
            return '';
        }
        
        $conditions = [];
        foreach ($this->having as $having) {
            if (isset($having['raw'])) {
                $conditions[] = $having['raw'];
            } else {
                $conditions[] = "{$having['column']} {$having['operator']} ?";
            }
        }
        
        return " HAVING " . implode(' AND ', $conditions);
    }
    
    /**
     * Get binding types for prepared statement
     */
    private function getBindingTypes() {
        $types = "";
        foreach ($this->bindings as $binding) {
            $types .= $this->getValueType($binding);
        }
        return $types;
    }
    
    /**
     * Get MySQL parameter type for value
     */
    private function getValueType($value) {
        if (is_int($value)) {
            return "i";
        } elseif (is_float($value) || is_double($value)) {
            return "d";
        } else {
            return "s";
        }
    }
}