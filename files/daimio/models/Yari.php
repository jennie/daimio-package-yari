<?php

/**
 * Placeholder for some Yari stuff
 *
 * @package yari
 * @author dann toliver
 * @version 1.0
 * @perms pleb
 */

class Yari
{
  
  /** 
  * Manipulate the profile data for another user 
  * @param string
  * @param string 
  * @return boolean 
  */ 
  static function user_profile($username, $params)
  {
    // get user_id from username
    $user_id = reset(UserLib::get_ids_from_usernames($username));
    
    if(!$user_id)
      return ErrorLib::set_error("User '$username' doesn't seem to exist");
    
    // check to see if a profile currently exists
    $profile_id = QueryLib::get_single_value('form__profiles', 'user_id', $user_id, 'id');
    
    // if it doesn't, add it w/ user_id
    if(!$profile_id)
      $profile_id = QueryLib::add_row('form__profiles', array('user_id' => $user_id));
    
    // use formtec to manipulate it
    return DataLib::input('profiles', $params, $profile_id);
  }
  
}
