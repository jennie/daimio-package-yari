<?php

/**
 * Article commands for the beta blog
 *
 * @package betablog
 * @author dann
 * @version 1.0
 * @copyright Bento Box Media, Inc 2002-2009
 * @perms anon
 */

class Article
{
  
  /** 
  * Return an array of tags and counts  
  * @return array 
  * @key __world
  */ 
  static function tag_cloud()
	{
		// TODO: refactor this function into something more useful (like the field itself, perhaps)
		
		// NOTE: specific to articles.tags field
		$form = 'articles';
		$field_keyword = 'tags';
		$conditions = array('form' => $form, 'keyword' => $field_keyword);
		$field_array = QueryLib::get_matching_rows('form_fields', $conditions);
		$tag_field = current($field_array);
		$field_id = $tag_field['id'];
    
		// set up resonable defaults
		$posterize = $posterize ? $posterize - 1 : 8;
		$limit = $limit ? $limit : 20;
		
		// set up the link params
    // foreach($_GET as $key => $value)
    //  if($key != $field_name)
    //    $link_params[$key] = $value;    
		
		// get some bogo fields to filter
    // if(count($this->filter))
    // {
    //  $form_fields = Forms::get_form_fields($this->form_id);
    //  list($where_array, $from_string) = Forms::get_where_array($form_fields, $this->filter, $this->form_id);
    //  $from_string = $from_string ? "$from_string, $table_name" : ",$table_name";
    // }
		
		// complete the where array
    // if(count($where_array))
    //  $where_array[] = "fog.response_id = $table_name.id";
		$where_array[] = "fog.field_id = $field_id";
		$where_array[] = "fog.that_id = tags.id";
		$where_array[] = "articles.id = fog.this_id AND articles.status = 'live'";
		$where_string = 'WHERE ' . implode("\n AND ", $where_array);
		
		$sql = "
						SELECT tags.*, count(1) as count
						FROM
						form_option_glue as fog,
						form_tags as tags,
						form__articles as articles
						$from_string
						$where_string
						GROUP BY stripped_value
						ORDER BY count DESC
						LIMIT 0,$limit
					 ";

		$data_array = QueryLib::make_data_array_from_query($sql);
		
		if(!count($data_array))
			return array();
		
		// do the posterization
		foreach($data_array as $chunk)
		{
			if(!$minimum || $chunk['count'] < $minimum)
				$minimum = $chunk['count'];
			if($chunk['count'] > $maximum)
				$maximum = $chunk['count'];
		}
		
		$stepsize = ($maximum - $minimum) / $posterize;
		$stepsize = ($stepsize < 1) ? 1 : $stepsize;
		foreach($data_array as $key => $chunk)
		{
			// set the count
			$stripped_values[$key] = $chunk['stripped_value'];
			$data_array[$key]['count'] = floor(($chunk['count'] - $minimum) / $stepsize) + 1;
			
			// set the link tag
      // $link_params[$field_name] = $chunk['stripped_value'];
      // $data_array[$key]['taglink'] = Http::make_link($GLOBALS['X']['page_id'], $link_params);
			
			// set the fieldname
      // $data_array[$key]['field_name'] = $field_name;
		}

		// sort the data magically...
		if($data_array)
			array_multisort($stripped_values, SORT_ASC, $data_array);
		
		// and send 'em home alive
		return $data_array;
	}
  
  
  /** 
  * Grab all article data and sort it by year / month / day
  * @return array 
  * @key __world
  */ 
  static function fetch_as_calendar()
  {
    $data = Data::fetch('articles', '', '', '', 'title,publish_date', '', '', '', 'publish_date DESC');
    foreach($data as $article)
    {
      $time = strtotime($article['publish_date']);
      if(date('Y', $time) < '1990' or $time > time())
        continue;
      
      $yearmonth = date('Y-m', $time);
      $day = date('j', $time);
      $nice_ym = date('F Y', $time);
      
      $fresh[$yearmonth]['articles'][] = $article;
      $fresh[$yearmonth][$day] += 1;
      $fresh[$yearmonth]['nice_ym'] = $nice_ym;
    }
    
    foreach($fresh as $ym => $stuff)
    {
      list($year, $month) = explode('-', $ym);
      $first_day_of_month = date('w', mktime(0, 0, 0, $month, 1, $year)); 
      $last_day_of_month = date('d', mktime(0, 0, 0, $month + 1, 0, $year));
      for ($i=0; $i < 42; $i++)
      { 
        $week = floor($i / 7);
        $day_of_week = $i % 7;
        $day_of_month = $i - $first_day_of_month + 1;
        if($day_of_month > $last_day_of_month || $day_of_month < 1)
          $day_of_month = '';

        if($stuff[$day_of_month])
          $fresh[$ym]['calendar'][$week][$day_of_week] = array('year' => $year, 'month' => $month, 'date' => $day_of_month, 'zdate' => sprintf("%02s", $day_of_month), 'articles' => $stuff[$day_of_month]);
        else
          $fresh[$ym]['calendar'][$week][$day_of_week]['date'] = $day_of_month;
      }
      $fresh[$ym]['count'] = count($fresh[$ym]['articles']);
    }
    
    krsort($fresh);
    return $fresh;
  }

}


// EOT