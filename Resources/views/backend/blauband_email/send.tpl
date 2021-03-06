{extends file="parent:backend/blauband_email/send.tpl"}

{block name="header"}
    {$smarty.block.parent}

    <script>
      var deleteSuccessSnippet = '{s namespace="blauband/mail" name="deleteSuccess"}{/s}';
    </script>
{/block}

{block name="mailContentWrapperAdditional"}
    {$smarty.block.parent}
    <div id="mailCustomSnippets">
        <label>{s namespace="blauband/mail" name="mailSnippets"}{/s}</label>
        <button type="button" class="saveAsSnippetBtn">{s namespace="blauband/mail" name="saveAsSnippet"}{/s}</button>

        <div class="customSnippetsDataWrapper">
            <div class="customSnippetsData" id="customSnippetsData{$id}">
                {foreach $customSnippets as $name => $content}
                    <div class="snippetRow">
                        <div class="snippetName">{$name}</div>

                        {foreach $content as $shopId => $data}
                            <div class="snippetLanguage">
                                <div class="snippetLocale" title="{$data['shopName']}">
                                    {assign iconLink "backend/_public/src/icons/flags/{$data['lang']}.gif"}
                                    <img src="{link file={$iconLink}}"/>
                                </div>
                                <div class="snippetLanguageValue">{$data['value']}</div>
                            </div>
                        {/foreach}

                        <div class="snippetEdit" data-snippetname="{$name}">
                            <img src="{link file="backend/_public/src/icons/pencil.png"}"/>
                        </div>

                        <div class="snippetDelete" data-snippetname="{$name}" data-url="{url controller="BlaubandEmailSnippets" action="delete"}">
                            <img src="{link file="backend/_public/src/icons/delete.png"}"/>
                        </div>

                        {foreach $content as $shopId => $data}
                            {if $authShopId == $shopId}
                                <div class="snippet">{str_replace('\n', '<br/>',$data['value'])}</div>
                            {/if}
                        {/foreach}
                    </div>
                {/foreach}
            </div>
        </div>
    </div>
{/block}