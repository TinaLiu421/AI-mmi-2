<?php
namespace App\Http\Controllers\Web;

use App\Http\Controllers\WebController;

class Visa_Options extends WebController {
    
    public function index() {
        $page_data = $this->loadModel('pages')->getByID(3, $this->_current_lang_index, 
        [
            'media_files' => 
            [
                ['type' => 'page', 'category' => 'banner_'.$this->_current_lang_index],
                ['type' => 'page', 'category' => 'mobile_banner_'.$this->_current_lang_index]
            ]
        ]);
        

        // set meta
        $this->pageMeta(
        [
            'title'         =>  (!empty($page_data['meta_title']))?$page_data['meta_title']:$page_data['title'],
            'description'   =>  $page_data['meta_description'],
            'image'         =>  $page_data['meta_image']
        ]);
        
        // get list
        $target_country = null;
        $target_country_id = $this->getParamValue('country', 0);
        $list_countries = $this->loadModel('pages', ['table' => 'country'])->getAll($this->_current_lang_index, [
            'media_files' => 
            [
                ['type' => 'page', 'category' => 'banner_'.$this->_current_lang_index],
                ['type' => 'page', 'category' => 'mobile_banner_'.$this->_current_lang_index]
            ]
        ], false);
        if(!empty($list_countries)) {
            foreach ($list_countries as $country) {
                if($country['id'] == $target_country_id) {
                    $target_country = $country;
                    
                    // replace banner if need
                    if(!empty($target_country['media_files']['banner_'.$this->_current_lang_index])) { 
                        $page_data['media_files']['banner_'.$this->_current_lang_index] = $target_country['media_files']['banner_'.$this->_current_lang_index];
                    }
                    if(!empty($target_country['media_files']['mobile_banner_'.$this->_current_lang_index])) { 
                        $page_data['media_files']['mobile_banner_'.$this->_current_lang_index] = $target_country['media_files']['mobile_banner_'.$this->_current_lang_index];
                    }
                }
            }
            if(empty($target_country)) {
                $target_country = reset($list_countries);
                $target_country_id = $target_country['id'];
                
                // replace banner if need
                if(!empty($target_country['media_files']['banner_'.$this->_current_lang_index])) { 
                    $page_data['media_files']['banner_'.$this->_current_lang_index] = $target_country['media_files']['banner_'.$this->_current_lang_index];
                }
                if(!empty($target_country['media_files']['mobile_banner_'.$this->_current_lang_index])) { 
                    $page_data['media_files']['mobile_banner_'.$this->_current_lang_index] = $target_country['media_files']['mobile_banner_'.$this->_current_lang_index];
                }
            }
        }
        
        // set banner
        if(!empty($page_data['media_files']['banner_'.$this->_current_lang_index])) { 
            foreach ($page_data['media_files']['banner_'.$this->_current_lang_index] as $banner_key => $banner) { 
                $page_data['media_files']['banner_'.$this->_current_lang_index][$banner_key]['url'] = $this->generateImage($banner, 1300, 245);
            }
        }
        if(!empty($page_data['media_files']['mobile_banner_'.$this->_current_lang_index])) { 
            foreach ($page_data['media_files']['mobile_banner_'.$this->_current_lang_index] as $banner_key => $banner) { 
                $page_data['media_files']['mobile_banner_'.$this->_current_lang_index][$banner_key]['url'] = $this->generateImage($banner, 800, 400);
            }
        }
        
        
        $list_data = $this->loadModel('pages', ['table' => 'visa'])->getAll($this->_current_lang_index, 
        [
            'where'         => 
            [
                ['ref_country', '=', $target_country_id]
            ],
            'media_files'   => 
            [
                ['type' => 'visa_option', 'category' => 'photo']
            ]
        ], false);
        if(!empty($list_data)) {
            foreach ($list_data as $visa_key => $visa) {
                if(!empty($visa['media_files']['photo'])) {
                    $list_data[$visa_key]['photo_url'] = $this->generateImage(reset($visa['media_files']['photo']), 750, 520, true);
                }
                else {
                    $list_data[$visa_key]['photo_url'] = $this->generateImage(null, 750, 520, true);
                }
                $list_data[$visa_key]['child_node'] = $this->loadModel('pages', ['table' => 'visa'])->getChildsNode($visa['id'], $this->_current_lang_index);
            }
        }
        
        // news
        $list_news = $this->loadModel('posts')->getAll( 
        [
            'show_type'         =>  1,
            'show_lang'         =>  $this->_current_lang_index,
            'show_page_size'    =>  12,
            'show_country'      =>  $target_country_id,
            'exclude_featured'  =>  true
        ]);

        if(!empty($list_news['data'])) {
            $list_news = $list_news['data'];
            foreach ($list_news as $news_key => $news) {
                if(empty($list_news[$news_key]['title'])) {
                    $list_news[$news_key]['title'] = mb_substr($this->toPlainText($news['content']), 0, 24);
                    if(md5($this->toPlainText(mb_substr($this->toPlainText($news['content']), 0, 24))) != md5($this->toPlainText(mb_substr($this->toPlainText($news['content']), 0, 25)))) {
                        $list_news[$news_key]['title'].= '...';
                    }
                }
                $list_news[$news_key]['url'] = $this->toURL('posts/details/'.$news['id']);
                if(!empty($news['photo'])) { 
                    $list_news[$news_key]['thumbnail'] = $this->generateImage(
                    [
                        'absolute_path' =>  'upload/member_posts/'.$news['photo'],
                        'file_path'     =>  'upload/member_posts/'.$news['photo']
                    ], 480, 320, true);
                }
                else {
                    $list_news[$news_key]['thumbnail'] = $this->generateImage(null, 480, 320, true);
                }
                $list_news[$news_key]['youtube_url'] = $this->getYoutubeEmbedUrl($news['youtube_url']);
            }
        }
        else {
            $list_news = false;
        }
 
        // events
        $list_events = $this->loadModel('posts')->getAll( 
        [
            'show_type'         =>  2,
            'show_lang'         =>  $this->_current_lang_index,
            'show_page_size'    =>  12,
            'show_country'      =>  $target_country_id,
            'exclude_featured'  =>  true
        ]);

        if(!empty($list_events['data'])) {
            $list_events = $list_events['data'];
            foreach ($list_events as $events_key => $events) {
                if(empty($list_events[$events_key]['title'])) {
                    $list_events[$events_key]['title'] = mb_substr($this->toPlainText($events['content']), 0, 24);
                    if(md5($this->toPlainText(mb_substr($this->toPlainText($events['content']), 0, 24))) != md5($this->toPlainText(mb_substr($this->toPlainText($events['content']), 0, 25)))) {
                        $list_events[$events_key]['title'].= '...';
                    }
                }
                $list_events[$events_key]['url'] = $this->toURL('posts/details/'.$events['id']);
                if(!empty($events['photo'])) { 
                    $list_events[$events_key]['thumbnail'] = $this->generateImage(
                    [
                        'absolute_path' =>  'upload/member_posts/'.$events['photo'],
                        'file_path'     =>  'upload/member_posts/'.$events['photo']
                    ], 480, 320, true);
                }
                else {
                    $list_events[$events_key]['thumbnail'] = $this->generateImage(null, 480, 320, true);
                }
                $list_events[$events_key]['youtube_url'] = $this->getYoutubeEmbedUrl($events['youtube_url']);
            }
        }
        else {
            $list_events = false;
        }
        
        // related agents & service_providers
        $agents = $this->_member_model->getAgentByCountryID($target_country['id']);
        
        $service_providers = [];
        $list_organization_type = $this->loadModel('pages', ['table' => 'organization_type'])->getAll($this->_current_lang_index, null, false);
        if(!empty($list_organization_type)) {
            foreach ($list_organization_type as $type) {
                $find_service_providers = $this->_member_model->getAllOnlySP($type['id'], $target_country['id']);
                if(!empty($find_service_providers)) {
                    if(empty($service_providers[$type['id']])) {
                        $service_providers[$type['id']] = $type;
                    }
                    $service_providers[$type['id']]['child_node'] = $find_service_providers;
                }
            }
        }
        
        // load view
        $this->pageCss('slick.min', 'asset/lib/slider', false);
        $this->pageScript('slick.min', 'asset/lib/slider', false);
        
        $this->pageOptions(
        [
            'countries' => $this->optionsToArray($list_countries)
        ]);
        
        return $this->pageData(
        [
            'target_country_id' =>  $target_country_id,
            'list'              =>  $list_data,
            'details'           =>  $page_data,
            'target_country'    =>  $target_country,
            'agents'            =>  $agents,
            'service_providers' =>  $service_providers,
            'list_news'         =>  $list_news,
            'list_events'       =>  $list_events
        ])->pageView();
    }
    
    public function details($id = 0) {
        $target_visa_options = $this->loadModel('pages', ['table' => 'visa'])->getByID((int)$id, $this->_current_lang_index);
        if(empty($target_visa_options)) {
            $this->doRedirect($this->toURL($this->_mapping_data['class']));
        }
        
        if($target_visa_options['parent_id'] == 0) {
            $this->doRedirect($this->toURL($this->_mapping_data['class']));
        }
        
        $target_visa_options_parent = $this->loadModel('pages', ['table' => 'visa'])->getByID((int)$target_visa_options['parent_id'], $this->_current_lang_index);
        
        $page_data = $this->loadModel('pages')->getByID(3, $this->_current_lang_index, 
        [
            'media_files' => 
            [
                ['type' => 'page', 'category' => 'banner_'.$this->_current_lang_index],
                ['type' => 'page', 'category' => 'mobile_banner_'.$this->_current_lang_index]
            ]
        ]);
        if(!empty($page_data['media_files']['banner_'.$this->_current_lang_index])) { 
            foreach ($page_data['media_files']['banner_'.$this->_current_lang_index] as $banner_key => $banner) { 
                $page_data['media_files']['banner_'.$this->_current_lang_index][$banner_key]['url'] = $this->generateImage($banner, 1300, 245);
            }
        }
        if(!empty($page_data['media_files']['mobile_banner_'.$this->_current_lang_index])) { 
            foreach ($page_data['media_files']['mobile_banner_'.$this->_current_lang_index] as $banner_key => $banner) { 
                $page_data['media_files']['mobile_banner_'.$this->_current_lang_index][$banner_key]['url'] = $this->generateImage($banner, 800, 400);
            }
        }
        
        $target_country = null;
        $target_country_id = $target_visa_options_parent['ref_country'];
        $list_countries = $this->loadModel('pages', ['table' => 'country'])->getAll($this->_current_lang_index, null, false);
        if(!empty($list_countries)) {
            foreach ($list_countries as $country) {
                if($country['id'] == $target_country_id) {
                    $target_country = $country;
                }
            }
            if(empty($target_country)) {
                $target_country = reset($list_countries);
                $target_country_id = $target_country['id'];
            }
        }
        
        // set meta
        $this->pageMeta(
        [
            'title'         =>  (!empty($target_visa_options['meta_title']))?$target_visa_options['meta_title']:$target_visa_options['title'],
            'description'   =>  $target_visa_options['meta_description'],
            'image'         =>  $target_visa_options['meta_image']
        ]);
        
        return $this->pageData(
        [
            'details'           =>  $page_data,
            'sub_details'       =>  $target_visa_options,
            'target_country'    =>  $target_country,
        ])->pageView();
    }
}