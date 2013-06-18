<?
/**
 * author: mytory
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