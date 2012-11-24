<?php

/**
 * Some coupon commands
 *
 * @package daimio
 * @author dann toliver
 * @version 1.0
 */

class Coupon
{
  
  // validates the item
  // TODO: move this somewhere else!
  private static function validize($item) {
    $collection = 'coupons';
    $fields = array('name', 'key', 'discount');
    
    if(!$item) return false;
    if($item['valid']) return true;
    
    foreach($fields as $key) 
      if($item[$key] === false) return false;
    
    // all clear!
    
    $update['valid'] = true;
    MongoLib::set($collection, $item['_id'], $update);
    
    return true;
  }
  
  
  /** 
  * Find some coupons 
  * @param string Coupon ids
  * @param string Coupon name
  * @param string Coupon verify path
  * @param string Supports sort, limit, skip, fields, nofields, count, i_can and attrs: {* (:limit 5 :skip 30 :sort {* (:name "-1")} :nofields (:pcache :scores))} or {* (:fields :name)} or {* (:count :true)} or {* (:tags :nifty)} or {* (:i_can :edit)}
  * @return string 
  * @key __member
  */ 
  static function find($by_ids=NULL, $by_name=NULL, $by_verify=NULL, $options=NULL)
  {
    if(isset($by_ids)) 
      $query['_id'] = array('$in' => MongoLib::fix_ids($by_ids));
    
    if(isset($by_name))
      $query['name'] = new MongoRegex("/$by_name/i");
    
    if(isset($by_verify)) 
      $query['status'] = $by_verify;
    
    return MongoLib::find_with_perms('coupons', $query, $options);
  }
  
  /** 
  * Add a coupon
  * @return string 
  * @key admin __exec
  */ 
  static function add()
  {
    $coupon['name'] = false;
    $coupon['key'] = false;
    $coupon['verify'] = false;
    $coupon['discount'] = false;
    $coupon['valid'] = false;
    $id = MongoLib::insert('coupons', $coupon);
    
    PermLib::grant_user_root_perms('coupons', $id);
    PermLib::grant_permission(array('coupons', $id), "admin:*", 'edit');
    // PermLib::grant_permission(array('coupons', $id), "world:*", 'view');
    
    History::add('coupons', $id, array('action' => 'add'));
    
    return $id;
  }
  
  /** 
  * Set the coupon's name 
  * @param string Coupon id
  * @param string New name
  * @return string 
  * @key admin __exec
  */ 
  static function set_name($id, $value)
  {
    if(!$coupon = reset(MongoLib::find_with_perms('coupons', $id)))
      return ErrorLib::set_error("That coupon is not within your domain");
    
    if($coupon['name'] == $value)
      return $id;
    
    // all clear!
    
    $update['name'] = $value;
    MongoLib::set('coupons', $id, $update);

    History::add('coupons', $id, array('action' => 'set_name', 'value' => $value));
    
    $coupon['name'] = $value;
    self::validize($coupon);
    
    return $id;
  }
  
  /** 
  * Set the coupon's key -- the string that triggers the coupon
  * @param string Coupon id
  * @param string New key
  * @return string 
  * @key admin __exec
  */ 
  static function set_key($id, $value)
  {
    if(!$coupon = reset(MongoLib::find_with_perms('coupons', $id)))
      return ErrorLib::set_error("That coupon is not within your domain");
    
    if($coupon['key'] == $value)
      return $id;
    
    // all clear!
    
    $update['key'] = $value;
    MongoLib::set('coupons', $id, $update);

    History::add('coupons', $id, array('action' => 'set_key', 'value' => $value));
    
    $coupon['key'] = $value;
    self::validize($coupon);
    
    return $id;
  }
  
  /** 
  * Set the coupon's discount as a percentage
  * @param string Coupon id
  * @param string New discount, which is a percentage
  * @return string 
  * @key admin __exec
  */ 
  static function set_discount($id, $value)
  {
    if(!$coupon = reset(MongoLib::find_with_perms('coupons', $id)))
      return ErrorLib::set_error("That coupon is not within your domain");
    
    if($coupon['discount'] == $value)
      return $id;
    
    // all clear!
    
    $update['discount'] = floatval($value);
    MongoLib::set('coupons', $id, $update);

    History::add('coupons', $id, array('action' => 'set_discount', 'value' => $value));
    
    $coupon['discount'] = $value;
    self::validize($coupon);
    
    return $id;
  }
  
  /** 
  * A coupon with verify checks the key against a path in the member's profile
  * @param string Coupon id
  * @param string Accepts a path, like "member.depot.coupon_code", which is checked for the coupon's key
  * @return string 
  * @key admin __exec
  */ 
  static function set_verify($id, $value)
  {
    if(!$coupon = reset(MongoLib::find_with_perms('coupons', $id)))
      return ErrorLib::set_error("That coupon is not within your domain");
    
    if($coupon['verify'] == $value)
      return $id;
    
    // all clear!
    
    $update['verify'] = $value;
    MongoLib::set('coupons', $id, $update);

    History::add('coupons', $id, array('action' => 'set_verify', 'value' => $value));
    
    $coupon['verify'] = $value;
    self::validize($coupon);
    
    return $id;
  }
  
  /** 
  * Clone a coupon
  * @param string Coupon id
  * @return string 
  * @key admin __exec
  */ 
  static function replicate($id)
  {
    if(!$coupon = reset(MongoLib::find_with_perms('coupons', $id)))
      return ErrorLib::set_error("That coupon is not within your domain");
    
    // all clear!
    
    $new_id = self::add();

    if($coupon['name']) 
      self::set_name($new_id, $coupon['name'] . ' _copy_');
    
    if($coupon['key']) 
      self::set_key($new_id, $coupon['key']);
    
    if($coupon['discount']) 
      self::set_discount($new_id, $coupon['discount']);
    
    if($coupon['verify']) 
      self::set_verify($new_id, $coupon['verify']);
    
    self::validize($coupon);

    return $new_id;
  }

}

// EOT