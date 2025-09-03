<?php
/**
 * HasTimestamps Trait - Automatic timestamp management
 * Phase 4 Implementation
 */

trait HasTimestamps {
    /**
     * Enable or disable timestamps
     */
    protected $timestamps = true;
    
    /**
     * Name of created_at field
     */
    protected $createdAtField = 'created_at';
    
    /**
     * Name of updated_at field
     */
    protected $updatedAtField = 'updated_at';
    
    /**
     * Date format for timestamps
     */
    protected $dateFormat = 'Y-m-d H:i:s';
    
    /**
     * Add timestamps to data before creating
     */
    protected function addCreateTimestamps($data) {
        if (!$this->timestamps) {
            return $data;
        }
        
        $now = $this->getCurrentTimestamp();
        
        if (!isset($data[$this->createdAtField])) {
            $data[$this->createdAtField] = $now;
        }
        
        if (!isset($data[$this->updatedAtField])) {
            $data[$this->updatedAtField] = $now;
        }
        
        return $data;
    }
    
    /**
     * Add updated_at timestamp to data before updating
     */
    protected function addUpdateTimestamps($data) {
        if (!$this->timestamps) {
            return $data;
        }
        
        if (!isset($data[$this->updatedAtField])) {
            $data[$this->updatedAtField] = $this->getCurrentTimestamp();
        }
        
        return $data;
    }
    
    /**
     * Get current timestamp in the specified format
     */
    protected function getCurrentTimestamp() {
        return date($this->dateFormat);
    }
    
    /**
     * Format a timestamp for display
     */
    protected function formatTimestamp($timestamp, $format = null) {
        if (empty($timestamp)) {
            return null;
        }
        
        $format = $format ?? 'M j, Y \a\t g:i A';
        
        if (is_string($timestamp)) {
            $timestamp = strtotime($timestamp);
        }
        
        return date($format, $timestamp);
    }
    
    /**
     * Get human-readable time difference
     */
    protected function getTimeDifference($timestamp, $suffix = true) {
        if (empty($timestamp)) {
            return 'Never';
        }
        
        $time = is_string($timestamp) ? strtotime($timestamp) : $timestamp;
        $diff = time() - $time;
        
        if ($diff < 60) {
            $result = $diff . ' second' . ($diff === 1 ? '' : 's');
        } elseif ($diff < 3600) {
            $minutes = floor($diff / 60);
            $result = $minutes . ' minute' . ($minutes === 1 ? '' : 's');
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            $result = $hours . ' hour' . ($hours === 1 ? '' : 's');
        } elseif ($diff < 2592000) {
            $days = floor($diff / 86400);
            $result = $days . ' day' . ($days === 1 ? '' : 's');
        } elseif ($diff < 31536000) {
            $months = floor($diff / 2592000);
            $result = $months . ' month' . ($months === 1 ? '' : 's');
        } else {
            $years = floor($diff / 31536000);
            $result = $years . ' year' . ($years === 1 ? '' : 's');
        }
        
        return $suffix ? $result . ' ago' : $result;
    }
    
    /**
     * Check if a timestamp is recent (within specified minutes)
     */
    protected function isRecent($timestamp, $minutes = 5) {
        if (empty($timestamp)) {
            return false;
        }
        
        $time = is_string($timestamp) ? strtotime($timestamp) : $timestamp;
        $diff = time() - $time;
        
        return $diff <= ($minutes * 60);
    }
    
    /**
     * Check if a timestamp is today
     */
    protected function isToday($timestamp) {
        if (empty($timestamp)) {
            return false;
        }
        
        $date = is_string($timestamp) ? date('Y-m-d', strtotime($timestamp)) : date('Y-m-d', $timestamp);
        return $date === date('Y-m-d');
    }
    
    /**
     * Check if a timestamp is within the last N days
     */
    protected function isWithinDays($timestamp, $days = 7) {
        if (empty($timestamp)) {
            return false;
        }
        
        $time = is_string($timestamp) ? strtotime($timestamp) : $timestamp;
        $cutoff = time() - ($days * 24 * 60 * 60);
        
        return $time >= $cutoff;
    }
    
    /**
     * Get the age of a record in days
     */
    protected function getAgeInDays($timestamp) {
        if (empty($timestamp)) {
            return null;
        }
        
        $time = is_string($timestamp) ? strtotime($timestamp) : $timestamp;
        $diff = time() - $time;
        
        return floor($diff / 86400);
    }
    
    /**
     * Get timestamp fields for the model
     */
    protected function getTimestampFields() {
        if (!$this->timestamps) {
            return [];
        }
        
        return [
            $this->createdAtField,
            $this->updatedAtField
        ];
    }
    
    /**
     * Convert timestamp to UTC
     */
    protected function toUtc($timestamp, $timezone = null) {
        if (empty($timestamp)) {
            return null;
        }
        
        $timezone = $timezone ?? date_default_timezone_get();
        $date = new DateTime($timestamp, new DateTimeZone($timezone));
        $date->setTimezone(new DateTimeZone('UTC'));
        
        return $date->format($this->dateFormat);
    }
    
    /**
     * Convert timestamp from UTC to local timezone
     */
    protected function fromUtc($timestamp, $timezone = null) {
        if (empty($timestamp)) {
            return null;
        }
        
        $timezone = $timezone ?? date_default_timezone_get();
        $date = new DateTime($timestamp, new DateTimeZone('UTC'));
        $date->setTimezone(new DateTimeZone($timezone));
        
        return $date->format($this->dateFormat);
    }
    
    /**
     * Get start and end of day for a given date
     */
    protected function getDayBoundaries($date = null) {
        $date = $date ?? date('Y-m-d');
        
        return [
            'start' => $date . ' 00:00:00',
            'end' => $date . ' 23:59:59'
        ];
    }
    
    /**
     * Get start and end of week for a given date
     */
    protected function getWeekBoundaries($date = null) {
        $timestamp = $date ? strtotime($date) : time();
        $dayOfWeek = date('w', $timestamp);
        $start = $timestamp - ($dayOfWeek * 24 * 60 * 60);
        $end = $start + (6 * 24 * 60 * 60);
        
        return [
            'start' => date('Y-m-d 00:00:00', $start),
            'end' => date('Y-m-d 23:59:59', $end)
        ];
    }
    
    /**
     * Get start and end of month for a given date
     */
    protected function getMonthBoundaries($date = null) {
        $date = $date ?? date('Y-m-d');
        $year = date('Y', strtotime($date));
        $month = date('m', strtotime($date));
        
        return [
            'start' => "{$year}-{$month}-01 00:00:00",
            'end' => date('Y-m-t 23:59:59', strtotime($date))
        ];
    }
    
    /**
     * Disable timestamps for this instance
     */
    public function withoutTimestamps() {
        $this->timestamps = false;
        return $this;
    }
    
    /**
     * Enable timestamps for this instance
     */
    public function withTimestamps() {
        $this->timestamps = true;
        return $this;
    }
}