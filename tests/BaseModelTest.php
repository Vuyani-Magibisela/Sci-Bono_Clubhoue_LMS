<?php
/**
 * Tests for BaseModel functionality
 */

require_once __DIR__ . '/TestFramework.php';
require_once __DIR__ . '/../app/Models/BaseModel.php';

class BaseModelTest {
    private $mockConnection;
    
    public function __construct($conn) {
        $this->mockConnection = $conn;
    }
    
    public function runTests() {
        $test = new TestFramework();
        
        $test->addTest('BaseModel Constructor', [$this, 'testConstructor']);
        $test->addTest('BaseModel Table Name Generation', [$this, 'testTableNameGeneration']);
        $test->addTest('BaseModel Parameter Type Detection', [$this, 'testParameterTypes']);
        
        $test->runTests();
    }
    
    public function testConstructor() {
        $model = new TestModel($this->mockConnection);
        
        TestFramework::assertNotNull($model, 'Model should be created');
        TestFramework::assertEquals('test_models', $model->getTable(), 'Table name should be generated correctly');
    }
    
    public function testTableNameGeneration() {
        $model = new TestModel($this->mockConnection);
        
        // Test that CamelCase is converted to snake_case and pluralized
        TestFramework::assertEquals('test_models', $model->getTable());
    }
    
    public function testParameterTypes() {
        $model = new TestModel($this->mockConnection);
        
        TestFramework::assertEquals('i', $model->testGetParamType(123));
        TestFramework::assertEquals('d', $model->testGetParamType(12.34));
        TestFramework::assertEquals('s', $model->testGetParamType('string'));
        TestFramework::assertEquals('b', $model->testGetParamType([]));
    }
}

// Test model for testing BaseModel functionality
class TestModel extends BaseModel {
    protected $table = null; // Let it auto-generate
    
    public function getTable() {
        return $this->table;
    }
    
    public function testGetParamType($value) {
        return $this->getParamType($value);
    }
}