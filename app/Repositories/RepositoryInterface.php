<?php
/**
 * Repository Interface - Contract for data access layer
 * Phase 4 Implementation
 */

interface RepositoryInterface {
    /**
     * Find entity by primary key
     */
    public function find($id);
    
    /**
     * Find all entities matching conditions
     */
    public function findAll($conditions = [], $orderBy = null, $limit = null);
    
    /**
     * Find first entity matching conditions
     */
    public function findFirst($conditions = [], $orderBy = null);
    
    /**
     * Create new entity
     */
    public function create(array $data);
    
    /**
     * Update entity by ID
     */
    public function update($id, array $data);
    
    /**
     * Delete entity by ID
     */
    public function delete($id);
    
    /**
     * Check if entity exists
     */
    public function exists($conditions);
    
    /**
     * Count entities matching conditions
     */
    public function count($conditions = []);
    
    /**
     * Execute raw query
     */
    public function query($sql, $params = []);
    
    /**
     * Begin database transaction
     */
    public function beginTransaction();
    
    /**
     * Commit database transaction
     */
    public function commit();
    
    /**
     * Rollback database transaction
     */
    public function rollback();
}