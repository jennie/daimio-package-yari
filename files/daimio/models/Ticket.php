<?php

/**
 * Ticket ticket ticket, I bought it on the train
 *
 * @package daimio
 * @author dann toliver
 * @version 1.0
 */

class Ticket
{
  
  /** 
  * Find some tickets 
  * @param string Ticket ids
  * @param string Event ids
  * @param string Supports sort, limit, skip, fields, nofields, count, i_can and attrs: {* (:limit 5 :skip 30 :sort {* (:name "-1")} :nofields (:pcache :scores))} or {* (:fields :name)} or {* (:count :true)} or {* (:tags :nifty)} or {* (:i_can :edit)}
  * @return string 
  * @key __member
  */ 
  static function find($by_ids=NULL, $by_event=NULL, $options=NULL)
  {
    if(isset($by_ids))
      $query['_id'] = array('$in' => MongoLib::fix_ids($by_ids));
    
    if(isset($by_event))
      $query['event'] = array('$in' => MongoLib::fix_ids($by_event));
    
    return MongoLib::find_with_perms('tickets', $query, $options);
  }
  
  /** 
  * Buy a ticket!
  * @param string Event id
  * @param string Purchase token
  * @param string Coupon code
  * @return string 
  * @key admin __exec
  */ 
  static function buy($event, $token, $coupon=NULL)
  {
    if(!$event = reset(MongoLib::find_with_perms('events', $event)))
      return ErrorLib::set_error("That event is not within your domain");
    
    if(!$event['valid'])
      return ErrorLib::set_error("Invalid event");
    
    $current_tickets = self::find(NULL, $event['_id']);
    
    if(count($current_tickets) >= $event['capacity'])
      return ErrorLib::set_error("That event is over capacity");
      
    if(!$token) // TODO: check this against stripe api?
      return ErrorLib::set_error("Invalid token");
    
    $user_id = $GLOBALS['X']['USER']['id'];
    
    
    // $paid_price = get_paid($token);
    
    $total_price = $event['price'];
    if($coupon) {      
      if(!$coupon = $event['coupons'][$coupon])
        return ErrorLib::set_error("Invalid coupon");
      
      if($coupon['verify']) {
        
        if(!$profile = reset(MongoLib::find_with_perms('profiles', $user_id)))
          return ErrorLib::set_error("You possess no profile, bro");
        
        if($coupon['key'] != Processor::get_nested_param_value($coupon['verify'], $profile))
          return ErrorLib::set_error("You do not possess the credentials to use that coupon");
      }
    }
    
    // if($paid_price != $event['price'])
    //   return ErrorLib::set_error("The paid price is not the actual price");
    
    
    
    // all clear!
    
    $ticket['token'] = $token;
    $ticket['user'] = $user_id;
    $ticket['event'] = $event['_id'];
    $ticket['coupon'] = $coupon['key'];
    $id = MongoLib::insert('tickets', $ticket);
    
    PermLib::grant_permission(array('tickets', $id), "admin:*", 'root');
    PermLib::grant_permission(array('tickets', $id), "user:$user_id", 'view');
    
    History::add('tickets', $id, array('action' => 'add'));
    
    return $id;
  }
  
}

// EOT