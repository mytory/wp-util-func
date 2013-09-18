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
        if( ! isset($post)){
            return '';
        }
        $img_src = NULL;

        $post_thumbnail_id = get_post_thumbnail_id();

        if($post_thumbnail_id){
            $tmp = wp_get_attachment_image_src($post_thumbnail_id);
            $img_src = $tmp[0];
        }else{
            preg_match("/<img.+?src=[\"'](.+?)[\"'].+?>/", $post->post_content, $imgs);
            if( ! empty($imgs)){
                $img_src = $imgs[1];
            }else{
                $img_src = '';
            }
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
     * post 형식 글의 single 페이지에 달 목록 버튼의 링크를 리턴한다. 
     * 1페이지에 들어가는 글의 개수는 워드프레스 읽기 설정으로 가정하고 계산한다. 
     * 자동으로 taxonomy와 term을 가져와서 링크를 만든다.
     * 여러 개의 taxonomy와 여러 개의 term에 속한 글인 경우엔
     * 워드프레스가 돌려준 배열의 첫 번째 놈을 선택해서 링크를 돌려 준다.
     * @param null $taxonomy
     * @param null $term
     * @return string|void
     */
    function fu_get_list_url($taxonomy = NULL, $term_slug = NULL){
        global $post, $table_prefix;

        if( ! $taxonomy){
            $taxonomies = get_post_taxonomies();
            $taxonomy = $taxonomies[0];
        }

        if( ! $term_slug){
            $terms = wp_get_post_terms($post->ID, $taxonomy);
            $term = $terms[0];
        }else{
            $term = get_term_by('slug', $term_slug, $taxonomy);
        }

        if($term){
            $sql = "SELECT SQL_CALC_FOUND_ROWS {$table_prefix}posts.ID
                FROM {$table_prefix}posts
                INNER JOIN {$table_prefix}term_relationships ON ( {$table_prefix}posts.ID = {$table_prefix}term_relationships.object_id )
                WHERE 1 =1
                AND (
                {$table_prefix}term_relationships.term_taxonomy_id
                IN ( $term->term_id )
                )
                AND {$table_prefix}posts.post_type = '{$post->post_type}'
                AND (
                {$table_prefix}posts.post_status = 'publish'
                OR {$table_prefix}posts.post_status = 'private'
                )
                GROUP BY {$table_prefix}posts.ID
                ORDER BY {$table_prefix}posts.post_date DESC";
            $result = mysql_query($sql);
            while($row = mysql_fetch_array($result)){
                $ids[] = $row['ID'];
            }

            $current_index = 0;
            foreach ($ids as $index => $ID) {
                if($ID == $post->ID){
                    $current_index = $index + 1;
                }
            }
            $curr_page = ceil($current_index / get_option('posts_per_page'));

            // term 로드 결과 있으면
            return get_term_link($term) . "/page/" . $curr_page;
        }else{

            $sql = "SELECT SQL_CALC_FOUND_ROWS {$table_prefix}posts.ID
                FROM {$table_prefix}posts
                WHERE 1 =1
                AND {$table_prefix}posts.post_type = '{$post->post_type}'
                AND (
                {$table_prefix}posts.post_status = 'publish'
                OR {$table_prefix}posts.post_status = 'private'
                )
                ORDER BY {$table_prefix}posts.post_date DESC";
            $result = mysql_query($sql);
            while($row = mysql_fetch_array($result)){
                $ids[] = $row['ID'];
            }

            $current_index = 0;
            foreach ($ids as $index => $ID) {
                if($ID == $post->ID){
                    $current_index = $index + 1;
                }
            }
            $curr_page = ceil($current_index / get_option('posts_per_page'));

            // 없으면(custom post type의 경우 term이 아예 없을 수 있다.)
            return home_url('/page/' . $curr_page . '/?post_type=' . $post->post_type);
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


if( ! function_exists('fu_get_post_navi')){
    /**
     * 언제나 작동하는 이전글, 다음글 함수
     * custom post type의 custom taxonomy에서도 잘 작동한다.
     * @param string $post_date
     * @param int $term_id
     * @param boolean $auto_term_id term_id를 입력하지 않은 경우 자동으로 불러오게 할 건지
     * @return array
     */
    function fu_get_post_navi($post_date, $term_id = 0, $auto_term_id = TRUE){
        global $post, $table_prefix;
        if( ! $term_id AND $auto_term_id){
            $taxonomies = get_post_taxonomies();
            $taxonomy = $taxonomies[0];
            $terms = wp_get_post_terms($post->ID, $taxonomy);
            $term = $terms[0];
            $term_id = $term->term_id;
        }

        $navi = array();

        if( ! $term_id AND ! $auto_term_id){
            $navi['older'] = get_previous_post();
            $navi['newer'] = get_next_post();
            return $navi;
        }

        // 이전 글 불러 오기
        $sql = "SELECT p.*
        FROM `{$table_prefix}posts` p, `{$table_prefix}term_relationships` r
        WHERE p.ID = r.object_id
            AND r.term_taxonomy_id = '$term_id'
            AND p.post_status = 'publish'
            AND p.post_date > '$post_date'
        ORDER BY post_date ASC
        LIMIT 1";
        $result = mysql_query($sql);
        if(mysql_num_rows($result) > 0){
            $navi['newer'] = (OBJECT)mysql_fetch_assoc($result);
        }

        // 다음 글 불러 오기
        $sql = "SELECT p.*
        FROM `{$table_prefix}posts` p, `{$table_prefix}term_relationships` r
        WHERE p.ID = r.object_id
            AND r.term_taxonomy_id = '$term_id'
            AND p.post_status = 'publish'
            AND p.post_date < '$post_date'
        ORDER BY post_date DESC
        LIMIT 1";
        $result = mysql_query($sql);
        if(mysql_num_rows($result) > 0){
            $navi['older'] = (OBJECT)mysql_fetch_assoc($result);
        }

        return $navi;
    }
}

if( ! function_exists('fu_get_count_no')){

    /**
     * 카테고리 등을 게시판 형식으로 보여 줄 때 앞에 붙일 번호에 사용할 숫자를 가져 오는 함수다.
     * 맨 윗쪽에 붙일 번호를 가져오므로, 루프돌면서 $count_no-- 를 해 주면 된다.
     * @return mixed
     */
    function fu_get_count_no(){
        global $wp_query;
        $paged = ( $wp_query->query_vars['paged'] ? $wp_query->query_vars['paged'] : 1 );
        return $wp_query->found_posts - ( $wp_query->query_vars['posts_per_page'] * ($paged - 1) );
    }
}
