<?
/**
 * author: mytory
 * 이 파일은 하위호환성을 보장하지 않습니다. 그냥 그 때 그 때 가져다 쓰세요.
 */

if( ! function_exists('fu_enable_hwp_attach')){
    /**
     * hwp 업로드 가능하도록.
     * @param  array $mime_arr
     * @return array
     */
    function fu_enable_hwp_attach($mime_arr){
        $mime_arr['hwp'] = 'application/haansofthwp';
        return $mime_arr;
    }
    add_filter('upload_mimes', 'fu_enable_hwp_attach');
}

if( ! function_exists('fu_get_img_src')){
    /**
     * 루프 안에서 글에 있는 이미지 src를 추출한다. 특성 이미지가 설정돼 있으면 특성 이미지를 가져 온다.
     * 특성 이미지가 없으면 본문에 나오는 첫 번째 img 태그를 파싱해서 경로를 가져 온다.
     * @return mixed
     */
    function fu_get_img_src(){
        global $post;
        $img_src = NULL;

        $post_thumbnail_id = get_post_thumbnail_id();

        if($post_thumbnail_id){
            $tmp = wp_get_attachment_image_src($post_thumbnail_id);
            $img_src = $tmp[0];
        }else{
            preg_match("/<img.+?src=[\"'](.+?)[\"'].+?>/", $post->post_content, $imgs);
            $img_src = $imgs[1];
        }
        return $img_src;
    }
}

if( ! function_exists('fu_get_thumb_src')){

    /**
     * 첨부 파일 이미지의 id를 받아서 원하는 사이즈로 썸네일을 만들고, src를 가져 오는 함수.
     * @param  int $attachment_id
     * @param  int $width
     * @param  int $height
     * @return string 썸네일의 URL
     */
    function fu_get_thumb_src($attachment_id, $width, $height){
        $filepath = get_attached_file($attachment_id);
        $upload_path = wp_upload_dir();
        $basedir = $upload_path['basedir'];

        // filepath가 $basedir까지 포함하고 있는 경우가 있음.
        $filepath = str_replace($basedir, '', $filepath);
        $fullpath = $basedir . $filepath;
        if( ! is_file($fullpath)){
            return FALSE;
        }
        $pathinfo = pathinfo($fullpath);
        $new_fullpath = $pathinfo['dirname'] . '/' . $pathinfo['filename'] . "-{$width}x{$height}" . '.' . $pathinfo['extension'];
        if( ! is_file($new_fullpath)){
            $image = wp_get_image_editor($fullpath);
            if ( ! is_wp_error( $image ) ) {
                $image->resize( $width, $height, false );
                $image->save( $new_fullpath );
            }
        }
        $new_filepath = str_replace($basedir, '', $new_fullpath);
        return $upload_path['baseurl'] . $new_filepath;
    }
}

if( ! function_exists('fu_get_list_url')){
    /**
     * post 형식 글의 single 페이지에 달 목록 버튼의 링크를 리턴한다. 일단, 리퍼러가 있으면 리퍼러를 출력한다.
     * 리퍼러가 없는 경우엔 자동으로 taxonomy와 term을 가져와서 링크를 만든다.
     * 여러 개의 taxonomy와 여러 개의 term에 속한 글인 경우엔
     * 워드프레스가 돌려준 배열의 첫 번째 놈을 선택해서 링크를 돌려 준다.
     * @param null|string $taxonomy
     * @param null|string $term
     * @return string|WP_Error
     */
    function fu_get_list_url($taxonomy = NULL, $term = NULL){
        global $post;
        if(isset($_SERVER['HTTP_REFERER'])){
            return $_SERVER['HTTP_REFERER'];
        }else{
            if( ! $taxonomy){
                $taxonomies = get_post_taxonomies();
                $taxonomy = $taxonomies[0];
            }
            if( ! $term){
                $terms = wp_get_post_terms($post->ID, $taxonomy);
                $term = $terms[0];
            }
            return get_term_link($term);
        }
    }
}

if( ! function_exists('fu_get_menu_item_info')){
    /**
     * 현재 페이지, 포스트, 카테고리의 메뉴에서의 위치를 파악해서 메뉴 아이템을 반환.
     * @return array
     */
    function fu_get_menu_item_info(){
        $queried_object = get_queried_object();
        if(isset($queried_object->taxonomy)){
            $object = $queried_object->taxonomy;
            $object_id = $queried_object->term_id;
        }else if(isset($queried_object->post_type)){
            $object = $queried_object->post_type;
            $object_id = $queried_object->ID;
        }

        if($queried_object->post_type == 'post'){
            $object = 'category';
            // TODO 포스트가 속한 카테고리 ID를 가져와야 한다.
            $object_id = $queried_object->ID;
        }

        $menu_items = wp_get_nav_menu_items('basic');
        $info = array();
        foreach ($menu_items as $item) {
            if($item->object == $object AND $item->object_id == $object_id){
                $info['current'] = $item;
            }
        }
        foreach ($menu_items as $item) {
            if($item->ID == $info['current']->menu_item_parent){
                $info['parent'] = $item;
            }
        }
        return $info;
    }
}
