<?php
abstract class Controller {
    protected $user;
    
    public function __construct() {
        $this->user = getCurrentUser();
    }
    
    protected function success($data = null, $message = 'Success', $status = 200) {
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $data,
            'timestamp' => time()
        ];
        jsonResponse($response, $status);
    }
    
    protected function error($message = 'Error', $status = 400, $errors = null) {
        $response = [
            'success' => false,
            'message' => $message,
            'timestamp' => time()
        ];
        
        if ($errors) {
            $response['errors'] = $errors;
        }
        
        jsonResponse($response, $status);
    }
    
    protected function requireAuth($roles = null) {
        if (!$this->user) {
            $this->error('Unauthorized', 401);
        }
        
        if ($roles && !in_array($this->user['role'], (array)$roles)) {
            $this->error('Access denied', 403);
        }
        
        return $this->user;
    }
    
    protected function getRequestData() {
        return getJSONInput();
    }
    
    protected function validate($data, $rules) {
        $errors = [];
        
        foreach ($rules as $field => $rule) {
            $ruleList = explode('|', $rule);
            $value = $data[$field] ?? null;
            
            foreach ($ruleList as $singleRule) {
                if ($singleRule === 'required' && empty($value)) {
                    $errors[$field] = "$field is required";
                    break;
                }
                
                if (strpos($singleRule, 'min:') === 0 && strlen($value) < substr($singleRule, 4)) {
                    $errors[$field] = "$field must be at least " . substr($singleRule, 4) . " characters";
                    break;
                }
                
                if (strpos($singleRule, 'max:') === 0 && strlen($value) > substr($singleRule, 4)) {
                    $errors[$field] = "$field must not exceed " . substr($singleRule, 4) . " characters";
                    break;
                }
                
                if ($singleRule === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $errors[$field] = "$field must be a valid email";
                    break;
                }
            }
        }
        
        if (!empty($errors)) {
            $this->error('Validation failed', 422, $errors);
        }
        
        return $data;
    }
}