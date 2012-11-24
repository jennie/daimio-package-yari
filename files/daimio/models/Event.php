<?php

/**
 * Some event stuff -- events are like a thing? that happens, like, at a time?
 *
 * @package daimio
 * @author dann toliver
 * @version 1.0
 */

class Event
{
  
  // validates the item
  // TODO: move this somewhere else!
  private static function validize($item) {
    $collection = 'events';
    $fields = array('name', 'capacity', 'price', 'start_date', 'end_date');
    
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
  * Find some events 
  * @param string Event ids
  * @param string Event name
  * @param string Search a start date range -- accepts (:yesterday :tomorrow) or (1349504624 1349506624)
  * @param string Supports sort, limit, skip, fields, nofields, count, i_can and attrs: {* (:limit 5 :skip 30 :sort {* (:name "-1")} :nofields (:pcache :scores))} or {* (:fields :name)} or {* (:count :true)} or {* (:tags :nifty)} or {* (:i_can :edit)}
  * @return string 
  * @key __member
  */ 
  static function find($by_ids=NULL, $by_name=NULL, $by_date_range=NULL, $options=NULL)
  {
    if(isset($by_ids)) 
      $query['_id'] = array('$in' => MongoLib::fix_ids($by_ids));
    
    if(isset($by_name))
      $query['name'] = new MongoRegex("/$by_name/i");
    
    if(isset($by_date_range)) {
      $begin_date = $by_date_range[0];
      $begin_date = ctype_digit((string) $begin_date) ? $begin_date : strtotime($begin_date);
      
      $end_date = $by_date_range[1];
      $end_date = ctype_digit((string) $end_date) ? $end_date : strtotime($end_date);
      
      $query['start_date']['$gt'] = new MongoDate($begin_date);
      $query['start_date']['$lt'] = new MongoDate($end_date);
    }
    
    return MongoLib::find_with_perms('events', $query, $options);
  }
  
  /** 
  * Add an event
  * @return string 
  * @key admin __exec
  */ 
  static function add()
  {
    $event['name'] = false;
    $event['price'] = false;
    $event['capacity'] = false;
    $event['start_date'] = false;
    $event['end_date'] = false;
    $event['coupons'] = array();
    $event['valid'] = false;
    $id = MongoLib::insert('events', $event);
    
    PermLib::grant_user_root_perms('events', $id);
    PermLib::grant_permission(array('events', $id), "admin:*", 'edit');
    // PermLib::grant_permission(array('events', $id), "world:*", 'view');
    
    History::add('events', $id, array('action' => 'add'));
    
    return $id;
  }
  
  /** 
  * Set the event's name 
  * @param string event id
  * @param string New name
  * @return string 
  * @key admin __exec
  */ 
  static function set_name($id, $value)
  {
    if(!$event = reset(MongoLib::find_with_perms('events', $id)))
      return ErrorLib::set_error("That event is not within your domain");
    
    if($event['name'] == $value)
      return $id;
    
    // all clear!
    
    $update['name'] = $value;
    MongoLib::set('events', $id, $update);

    History::add('events', $id, array('action' => 'set_name', 'value' => $value));
    
    $event['name'] = $value;
    self::validize($event);
    
    return $id;
  }
  
  /** 
  * Set the event's capacity 
  * @param string event id
  * @param string New capacity
  * @return string 
  * @key admin __exec
  */ 
  static function set_capacity($id, $value)
  {
    if(!$event = reset(MongoLib::find_with_perms('events', $id)))
      return ErrorLib::set_error("That event is not within your domain");

    // TODO: check value
        
    if($event['capacity'] == $value)
      return $id;
    
    // all clear!
    
    $update['capacity'] = $value;
    MongoLib::set('events', $id, $update);

    History::add('events', $id, array('action' => 'set_capacity', 'value' => $value));
    
    $event['capacity'] = $value;
    self::validize($event);
    
    return $id;
  }
  
  /** 
  * Set the event's price 
  * @param string event id
  * @param string New price
  * @return string 
  * @key admin __exec
  */ 
  static function set_price($id, $value)
  {
    if(!$event = reset(MongoLib::find_with_perms('events', $id)))
      return ErrorLib::set_error("That event is not within your domain");
    
    // TODO: check value
    
    if($event['price'] == $value)
      return $id;
    
    // all clear!
    
    $update['price'] = floatval($value);
    MongoLib::set('events', $id, $update);

    History::add('events', $id, array('action' => 'set_price', 'value' => $value));
    
    $event['price'] = $value;
    self::validize($event);
    
    return $id;
  }
  
  /** 
  * Set the event's start date 
  * @param string event id
  * @param string New start date
  * @return string 
  * @key admin __exec
  */ 
  static function set_start_date($id, $value)
  {
    if(!$event = reset(MongoLib::find_with_perms('events', $id)))
      return ErrorLib::set_error("That event is not within your domain");
    
    if(!$value = new MongoDate(ctype_digit((string) $value) ? $value : strtotime($value)))
      return ErrorLib::set_error("That is not a valid date");
    
    if($event['start_date'] == $value)
      return $id;
    
    // all clear!
    
    $update['start_date'] = $value;
    MongoLib::set('events', $id, $update);

    History::add('events', $id, array('action' => 'set_start_date', 'value' => $value));
    
    $event['start_date'] = $value;
    self::validize($event);
    
    return $id;
  }
  
  /** 
  * Set the event's end date 
  * @param string event id
  * @param string New end date
  * @return string 
  * @key admin __exec
  */ 
  static function set_end_date($id, $value)
  {
    if(!$event = reset(MongoLib::find_with_perms('events', $id)))
      return ErrorLib::set_error("That event is not within your domain");
    
    if(!$value = new MongoDate(ctype_digit((string) $value) ? $value : strtotime($value)))
      return ErrorLib::set_error("That is not a valid date");
    
    if($event['end_date'] == $value)
      return $id;
    
    // all clear!
    
    $update['end_date'] = $value;
    MongoLib::set('events', $id, $update);

    History::add('events', $id, array('action' => 'set_end_date', 'value' => $value));
    
    $event['end_date'] = $value;
    self::validize($event);
    
    return $id;
  }
  
  
  /** 
  * Attach a coupon to an event
  * @param string Event id
  * @param string Coupon id
  * @return string 
  * @key admin __exec
  */ 
  static function add_coupon($id, $coupon)
  {
    if(!$event = reset(MongoLib::find_with_perms('events', $id)))
      return ErrorLib::set_error("That event is not within your domain");
    
    if(!$coupon = reset(MongoLib::find_with_perms('coupons', $coupon)))
      return ErrorLib::set_error("That coupon is not in your purview");
    
    // all clear!
    
    $coupons = $event['coupons'];
    unset($coupon['pcache']);
    unset($coupon['perms']);
    $coupons[$coupon['key']] = $coupon;

    $update['coupons'] = $coupons;
    MongoLib::set('events', $id, $update);

    History::add('events', $id, array('action' => 'add_coupon', 'coupon' => $coupon['_id']));
    
    return $id;
  }
  
  /** 
  * Remove a coupon from an event 
  * @param string Event id
  * @param string Coupon id
  * @return string 
  * @key admin __exec
  */ 
  static function remove_coupon($id, $coupon)
  {
    if(!$event = reset(MongoLib::find_with_perms('events', $id)))
      return ErrorLib::set_error("That event is not within your domain");
    
    if(!$coupon = reset(MongoLib::find_with_perms('coupons', $coupon)))
      return ErrorLib::set_error("That coupon is not in your purview");
    
    // all clear!
    
    $coupons = $events['coupons'];
    unset($coupons[$coupon['key']]);
    
    $update['coupons'] = $coupons;
    MongoLib::set('events', $id, $update);

    History::add('events', $id, array('action' => 'remove_coupon', 'coupon' => $coupon['_id']));
    
    return $id;
  }
  
  
  
  /** 
  * Clone a event
  * @param string event id
  * @return string 
  * @key admin __exec
  */ 
  static function replicate($id)
  {
    if(!$event = reset(MongoLib::find_with_perms('events', $id)))
      return ErrorLib::set_error("That event is not within your domain");
    
    // all clear!
    
    $new_id = self::add();
    
    if($event['name']) 
      self::set_name($new_id, $event['name'] . ' _copy_');
    
    if($event['capacity']) 
      self::set_capacity($new_id, $event['capacity']);
    
    if($event['price']) 
      self::set_price($new_id, $event['price']);
    
    if($event['start_date']) 
      self::set_start_date($new_id, $event['start_date']->sec);
    
    if($event['end_date']) 
      self::set_end_date($new_id, $event['end_date']->sec);
    
    if($event['coupons'])
      foreach($event['coupons'] as $coupon)
        self::add_coupon($new_id, $coupon['_id']);
    
    self::validize($event);

    return $new_id;
  }

}

// EOT