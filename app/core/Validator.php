<?php

namespace app\core;

class Validator
{
  private $validationResults = [];
  private $validating = [];
  private $target;
  private $param;
  private $location;

  public function __construct()
  {
    $this->validating['body'] = Application::$app->request->getBody();
    $this->validating['query'] = Application::$app->request->getQuery();
  }

  public function bodyValidation(String $target)
  {
    $this->target = Application::$app->request->getBody($target);
    $this->param = $target;
    $this->location = 'body';
  }

  public function queryValidation(String $target)
  {
    $this->target = Application::$app->request->getQuery($target);
    $this->param = $target;
    $this->location = 'query';
  }

  public function isNotNull(String $errorMsg = '')
  {
    if ($this->target == null) {
      $this->setError($errorMsg != null ? $errorMsg : 'This Field Cannot Be Empty', $this->param, $this->location);
    }
    return $this;
  }

  public function isNotIn(array $target, String $errorMsg = '')
  {
    if (in_array($this->target, $target)) {
      $this->setError($errorMsg != null ? $errorMsg : "Please Don't Use '$target' Word on This Field", $this->param, $this->location);
    }
    return $this;
  }

  public function isEmail(String $errorMsg = '')
  {
    if (!filter_var($this->target, FILTER_VALIDATE_EMAIL)) {
      $this->setError($errorMsg != null ? $errorMsg : 'Invalid Email Address', $this->param, $this->location);
    }
    return $this;
  }

  public function isString(String $errorMsg = '')
  {
    if (!is_string($this->target)) {
      $this->setError($errorMsg != null ? $errorMsg : 'Invalid Email Address', $this->param, $this->location);
    }
    return $this;
  }

  public function isInt(String $errorMsg = '')
  {
    if (!filter_var($this->target, FILTER_VALIDATE_INT)) {
      $this->setError($errorMsg != null ? $errorMsg : 'Numeric Only', $this->param, $this->location);
    }
    return $this;
  }

  public function isUrl(String $errorMsg = '')
  {
    if (!filter_var($this->target, FILTER_VALIDATE_URL)) {
      $this->setError($errorMsg != null ? $errorMsg : 'invalid URL', $this->param, $this->location);
    }
    return $this;
  }

  public function isAlpha(String $errorMsg = '')
  {
    if (!ctype_alpha($this->target)) {
      $this->setError($errorMsg != null ? $errorMsg : 'Alphabet Only', $this->param, $this->location);
    }
    return $this;
  }

  public function isAlphaNumeric(String $errorMsg = '')
  {
    if (!ctype_alnum($this->target)) {
      $this->setError($errorMsg != null ? $errorMsg : 'Alphabet and Numeric Only', $this->param, $this->location);
    }
    return $this;
  }

  public function isAlphaSpace(String $errorMsg = '')
  {
    if (!ctype_alpha(str_replace(' ', '', $this->target))) {
      $this->setError($errorMsg != null ? $errorMsg : 'Alphabet and Space Only', $this->param, $this->location);
    }
    return $this;
  }

  public function isLength(array $options = [], String $errorMsg = '')
  {
    if (!($options['min'] <= strlen($this->target) && strlen($this->target) <= $options['max'])) {
      $this->setError($errorMsg != null ? $errorMsg : "This Field Can Only Minimum $options[min] and Maximum $options[max] Characters", $this->param, $this->location);
    }
    return $this;
  }

  public function equals(String $target, String $errorMsg = '')
  {
    if ($this->target !== $target) {
      $this->setError($errorMsg != null ? $errorMsg : $this->param . " Does Not Match", $this->param, $this->location);
    }
    return $this;
  }

  public function regexMatches(String $regex, String $errorMsg = '')
  {
    /*
     * example : preg_match('/^[a-z\s]*$/i', $this->target);
     */
    if (preg_match($regex, $this->target)) {
      $this->setError($errorMsg != null ? $errorMsg : 'Invalid Value', $this->param, $this->location);
    }
    return $this;
  }

  public function trim($char = '')
  {
    switch ($this->location) {
      case 'body':
        Application::$app->request->setBody($this->param, trim($this->target, $char));
        break;
      case 'query':
        Application::$app->request->setQuery($this->param, trim($this->target, $char));
        break;
    }
    return $this;
  }

  public function sanitize($type = 'SPECIAL_CHARS')
  {
    switch ($this->location) {
      case 'body':
        switch ($type) {
          case 'STRING':
            Application::$app->request->setBody($this->param, filter_var($this->target, FILTER_SANITIZE_STRING));
            break;
          case 'URL':
            Application::$app->request->setBody($this->param, filter_var($this->target, FILTER_SANITIZE_URL));
            break;
          case 'SPECIAL_CHARS':
            Application::$app->request->setBody($this->param, filter_var($this->target, FILTER_SANITIZE_SPECIAL_CHARS));
            break;
        }
        break;
      case 'query':
        switch ($type) {
          case 'STRING':
            Application::$app->request->setQuery($this->param, filter_var($this->target, FILTER_SANITIZE_STRING));
            break;
          case 'URL':
            Application::$app->request->setQuery($this->param, filter_var($this->target, FILTER_SANITIZE_URL));
            break;
          case 'SPECIAL_CHARS':
            Application::$app->request->setQuery($this->param, filter_var($this->target, FILTER_SANITIZE_SPECIAL_CHARS));
            break;
        }
        break;
    }
    return $this;
  }

  public function custom($callback)
  {
    try {
      $heiboy = call_user_func($callback, $this->target, $this->validating);
      if ($heiboy != null) {
        throw new \Error($heiboy->getMessage());
      }
    } catch (\Throwable $err) {
      $this->setError($err->getMessage(), $this->param, $this->location);
    }
    return $this;
  }

  public function setError($msg, $param, $location)
  {
    foreach ($this->validationResults as $key => $value) {
      if (in_array($param, $value)) {
        return;
        // unset($this->validationResults[$key]);
      }
    }

    array_push($this->validationResults, [
      "location" => $location,
      "msg" => "$msg",
      "param" => "$param"
    ]);
  }

  public function validateResults()
  {
    return $this->validationResults;
  }
}
