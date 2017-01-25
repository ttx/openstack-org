/**
 * Copyright 2014 Openstack Foundation
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * http://www.apache.org/licenses/LICENSE-2.0
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 **/
jQuery(document).ready(function($){

    $('#back_to_news').click(function(event){
        window.location = $(this).attr('data-url');
    });

    $('#go_to_recent').click(function(){
        $("html, body").animate({ scrollTop: $('.newsStandBy').offset().top}, 1000);
        return false;
    });

    $( "#slider_sortable, #featured_sortable" ).sortable({
        items: "tr:not(.placeholder_empty)",
        connectWith: ".connected",
        revert: false,
        placeholder: "placeholder",
        update: function(event,ui) {
            if (ui.sender) {
                if ($(this).children().length > 6) {
                    $(ui.sender).sortable('cancel');
                } else {
                    saveSortArticle(ui.item,true);
                }
            } else {
                saveSortArticle(ui.item,false);
            }
        }
    }).disableSelection();

    $( "#recent_sortable, #standby_sortable" ).sortable({
        connectWith: ".connected",
        revert: false,
        placeholder: "placeholder",
        update: function(event,ui) {
            var is_new = (ui.sender);
            saveSortArticle(ui.item,is_new);
        }
    }).disableSelection();

    $('.newsDelete').click(function(){
        if (confirm("Do you wish to delete this article?")) {
            deleteArticle($(this).parents('.article_row'));
        }
    });

    $('.newsRemove').click(function(){
        archiveArticle($(this).parents('.article_row'));
    });

    $('.image','.article_row').popover({
        placement: 'left',
        content: function(){
            return $('.image_html',this).html();
        },
        html: true,
        trigger: 'hover',
        container: 'body'}
    );

    // level columns heights
    $('.right-col').height($('.newsFeatured').height());
    $('.right-col').scrollTop(200);


});

function saveSortArticle(item,is_new) {
    var old_rank = jQuery('.article_rank',item).val();
    var new_rank = item.index() + 1;
    var article_id = jQuery('.article_id',item).val();
    var article_type = jQuery('.article_type',item).val();
    var target = jQuery(item).parents('tbody').attr('id').split('_')[0];

    is_new = (is_new) ? 1 : 0;

    jQuery.ajax({
        type: "POST",
        url: 'NewsAdminPage_Controller/setArticleRank',
        data: { id : article_id, old_rank : old_rank, new_rank : new_rank, type : article_type, target: target, is_new : is_new },
        success: function(){ //update ranks
            if (article_type == target) {
                jQuery('.article_rank','#'+article_type+'_sortable').each(function(index){
                    jQuery(this).val(index+1);
                });
            } else {
                jQuery('.article_type',item).val(target);
                jQuery('.article_rank','#'+target+'_sortable').each(function(index){
                    jQuery(this).val(index+1);
                });
                jQuery('.newsDelete',item).off('click').html('<i class="fa fa-archive"></i>').removeClass().addClass('newsRemove').click(function(){
                    archiveArticle($(this).parents('.article_row'));
                });

            }
        }
    });
}

function deleteArticle(article) {
    var article_id = jQuery('.article_id',article).val();

    jQuery.ajax({
        type: "POST",
        url: 'NewsAdminPage_Controller/deleteArticle',
        data: { id : article_id},
        success: function(){
            jQuery(article).remove();
        }
    });
}

function archiveArticle(article) {
    var article_id = jQuery('.article_id',article).val();
    var article_type = jQuery('.article_type',article).val();
    var old_rank = jQuery('.article_rank',article).val();

    jQuery.ajax({
        type: "POST",
        url: 'NewsAdminPage_Controller/archiveArticle',
        data: { id : article_id, type : article_type, old_rank : old_rank},
        success: function(){
            jQuery(article).remove();
        }
    });
}

