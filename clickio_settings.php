<?php
/**
 * Plugin settings page
 */

// @codingStandardsIgnoreFile

use Clickio as org;
use Clickio\ExtraContent\ExtraContent;
use Clickio\ExtraContent\ExtraContentServiceFactory;
use Clickio\Logger\Interfaces\ILogger;
use Clickio\Prism\Cache\CacheRepo;
use Clickio\Utils\FileSystem;

$plugin = org\ClickioPlugin::getInstance();

?>
<div id="clickio-prism-wp-plugin">
    <div id="cl-settings" style="display: none">
        <div id="cl-logo"></div>
        <form action="options.php" method="post">
            <?php
                settings_fields(org\Options::OPT_KEY);
                do_settings_sections(org\Options::OPT_KEY);
            ?>
            <div id="tabs">
                <ul class="nav-tab-wrapper">
                    <li class="nav-tab ui-state-active"><a href="#mobile">Mobile</a></li>
                    <li class="nav-tab"><a href="#amp">AMP</a></li>
                    <li class="nav-tab"><a href="#cache">Cache</a></li>
                    <?php if (org\Options::get('plugin_advanced_mode', null) == 1) : ?>
                        <li class="nav-tab"><a href="#advanced">Advanced</a></li>
                        <li class="nav-tab"><a href="#extra_content">Extra Content</a></li>
                    <?php endif; ?>
                </ul>



                <div id="mobile" class="tab-content active">
                    <h2>Mobile Settings</h2>
                    <p>
                        <?php if (org\Options::get('integration_scheme', 'dns') != 'dns') : ?>
                            <div class="important-setting">
                                <input
                                    type="checkbox"
                                    name="<?php echo org\Options::OPT_KEY ?>[mobile]"
                                    id="mobredir"
                                    value="1"
                                    data-confirm="1"
                                    <?php echo org\Options::get("mobile", 0) == 1? 'checked' : '' ?>
                                    >
                                <label for="mobredir">
                                    Enable Prism Mobile
                                    <small>(Redirect mobile visitors to Prism Mobile version of your site)</small>
                                </label>
                            </div>
                            <?php if (org\Options::get("mobile", 0) == 0) : ?>
                                <div class="notification">
                                    <div class="error">
                                        <div class="content">
                                            Please do not enable Prism Mobile before receiving conformation from
                                            the Clickio account manager, that Prism is ready to go live.
                                        </div>
                                    </div>
                                </div>
                            <?php endif ?>
                        <?php else : ?>
                            <div class="mobile_unavailable">
                                <h4>
                                    Prism mobile version is working via Clickio CDN.
                                    <p>
                                        If you want to disable it or change any settings -
                                        please contact your account manager or write an email to
                                        <a href="mailto:prism-support@clickio.com">
                                            prism-support@clickio.com.
                                        </a>
                                    </p>
                                    <input
                                        class="hidden"
                                        type="checkbox"
                                        name="<?php echo org\Options::OPT_KEY ?>[mobile]"
                                        id="mobredir"
                                        value="1"
                                        data-confirm="1"
                                        <?php echo org\Options::get("mobile", 0) == 1? 'checked' : '' ?>
                                    >
                                </h4>
                                <hr>
                            </div>
                        <?php endif ?>
                    </p>
                    <p>
                        <label for="customtypes">Custom post types:</label>
                        <input
                            type="text"
                            name="<?php echo org\Options::OPT_KEY ?>[customtypes]"
                            id="customtypes"
                            value="<?php echo org\Options::get("customtypes", "") ?>"></p>
                    </p>
                    <p>If you use custom post types, insert it names to this field. Example: <code>type1,type2</code></p>
                </div>



                <div id="amp" class="tab-content">
                    <h2>AMP Settings</h2>
                    <p>
                        <!-- <div class="important-setting">
                            <input
                                type="checkbox"
                                name="<?php echo org\Options::OPT_KEY ?>[useamp]"
                                id="useamp"
                                value="1"
                                data-confirm="1"
                                <?php echo org\Options::get("useamp", 0) == 1? "checked" : "" ?>
                                >
                            <label for="useamp">
                                Enable Prism AMP
                                <small>(Add link to Prism AMP)</small>
                            </label>
                        </div>
                        <?php if (org\Options::get("useamp", 0) == 0) : ?>
                            <div class="notification">
                                <div class="error">
                                    <div class="content">
                                        Please do not enable Prism AMP before receiving conformation from
                                        the Clickio account manager, that Prism is ready to go live.
                                    </div>

                                </div>
                            </div>
                        <?php endif ?> -->
                    </p>
                    <h3>Content types for AMP</h3>
                    <p>
                        Choose the content types for which AMP will be enabled.
                        <br>Please select only the types that you use for primary content on your site (articles, news, posts, etc.):</p>
                    <p>
                        <input
                            type="checkbox"
                            name="<?php echo org\Options::OPT_KEY ?>[posts]"
                            id="posts"
                            value="1"
                            <?php echo org\Options::get('posts', 0) == 1? 'checked' : '' ?>
                            >
                        <label for="posts">Posts</label>
                    </p>
                    <p>
                        <input
                            type="checkbox"
                            name="<?php echo org\Options::OPT_KEY ?>[pages]"
                            id="pages"
                            value="1"
                            <?php echo org\Options::get('pages', 0) == 1 ? 'checked' : '' ?>
                            >
                        <label for="pages">Pages</label>
                    </p>
                </div>

                <div id="cache" class="tab-content">
                    <h2>Cache Settings</h2>
                    <p>
                        <div class="important-setting">
                            <input
                                type="checkbox"
                                name="<?php echo org\Options::OPT_KEY ?>[cache]"
                                id="cache_value"
                                value="1"
                                <?php echo org\Options::get("cache", 0) == 1? "checked" : "" ?>
                                >
                            <label for="cache_value">
                                Page cache
                            </label>
                        </div>
                    </p>

                    <div>
                    <label for="lifetime">
                        Page cache lifetime
                    </label>
                    <input
                        type="text"
                        name="<?php echo org\Options::OPT_KEY ?>[cache_lifetime]"
                        id="lifetime"
                        value="<?php echo org\Options::get('cache_lifetime', 0) ?>"
                        >
                        seconds
                    </div>
                    <small>The higher the value, the larger the cache</small>

                    <?php if(org\Options::get('plugin_advanced_mode') == 1): ?>
                    <div>
                        <h3>Cache Status</h3>
                        <p>
                            <div>
                                Cache size:
                                <?php
                                    $size = (CacheRepo::getInstance())->getCacheSize();
                                    if ($size > 0) {
                                        echo size_format("$size B", 2);
                                    } else {
                                        echo "$size B";
                                    }
                                ?>
                            </div>
                            <div>
                                Free space:
                                <?php
                                    $free = FileSystem::getDiskFreeSpace();
                                    if ($free > 0) {
                                        echo size_format("$free B", 2);
                                    } else {
                                        echo "$free B";
                                    }
                                ?>
                                /
                                <?php echo FileSystem::getDiskFreeSpacePercent()?>%
                            </div>
                        </p>
                    </div>
                    <?php endif; ?>
                </div>


                <div id="advanced" class="tab-content <?php echo org\Options::get('plugin_advanced_mode', null) == 1? '' : 'hidden'?>">
                    <h2>Advanced</h2>
                    <p>
                        <input
                            type="checkbox"
                            name="<?php echo org\Options::OPT_KEY ?>[redir]"
                            id="ampredir"
                            value="1"
                            <?php echo org\Options::get('redir', 0) == 1? 'checked' : '' ?>
                            >
                        <label for="ampredir">
                            Turn ON Mobile Redirection
                            <small>(Redirect all mobile visitors to AMP version of your site)</small>
                        </label>
                    </p>
                    <p>
                        <input
                            type="checkbox"
                            name="<?php echo org\Options::OPT_KEY ?>[is_debug]"
                            id="is_debug"
                            value="1"
                            <?php echo org\Options::get('is_debug', 0) == 1? 'checked' : '' ?>
                            >
                        <label for="is_debug">
                            Debug mode
                            <small>(Turn off when not debugging.)</small>
                        </label>

                    </p>
                    <p>
                        <label for="log_level">
                            Log level
                        </label>
                        <select
                            name="<?php echo org\Options::OPT_KEY ?>[log_level]"
                            id="log_level"
                            value="<?php echo org\Options::get('log_level', ILogger::LOGGER_ON)?>"
                            >
                            <?php foreach($plugin->getLogger()->getLevelMap() as $level => $label): ?>
                                <option value="<?php echo $level ?>"
                                <?php echo org\Options::get('log_level', ILogger::LOGGER_ON) == $level ? "selected" : "" ?>
                                ><?php echo $label ?></option>
                            <?php endforeach; ?>
                        </select>
                        <br>
                        <small>Log location: <?php echo $plugin->getLogger()->getLogDir() ?></small>
                    </p>

                    <h3>Available cache cleaners:</h3>
                    <ul class="cleaners-list">
                        <?php
                            foreach ($plugin->getCache()->getServices() as $srv) :
                                $cls_list = explode('\\', get_class($srv));
                                $srv_cls = array_pop($cls_list);
                        ?>
                            <li class="<?php echo $srv_cls ?>">
                                <div>
                                    <label class="<?php echo $srv_cls == "ClickIoCDN" || ($srv_cls == "Plugin" && org\Options::get('integration_scheme') == 'cms')? "disabled" : "" ?> ">
                                    <input
                                        type="checkbox"
                                        name="<?php echo org\Options::OPT_KEY ?>[cleaners][]"
                                        value="<?php echo $srv_cls ?>"
                                        class="<?php echo ($srv_cls == "ClickIoCDN" || ($srv_cls == "Plugin" && org\Options::get('integration_scheme') == 'cms') ? "disabled" : "") ?>"
                                        <?php echo ((in_array($srv_cls, org\Options::get('cleaners', [])) || $srv_cls == "ClickIoCDN" || ($srv_cls == "Plugin" && org\Options::get('integration_scheme') == 'cms')) ? 'checked' : '') ?>
                                        >
                                        <b><?php echo $srv->getLabel() ?></b>
                                    </label>
                                    <div><?php echo $srv->getDescription() ?> </div>
                                    <br>
                                </div>
                            </li>
                            <?php if ($srv_cls == 'NginxLocal') : ?>
                                <div class="cache_location <?php echo (in_array($srv_cls, org\Options::get('cleaners', [])) ? "" : "invisible") ?>">
                                    <h3>Local cache location</h3>
                                    <p>
                                        Enter local cache locations, each locations from a new line, example:
                                    <br>
                                        <code>/var/www/vhosts/yoursite.com/cache/mobile</code><br>
                                        <code>/var/cache/yoursite.com/desktop</code><br>
                                    </p>
                                    <p>
                                        <!-- Do not move value to the next line due to spaces -->
                                        <textarea
                                            name="<?php echo org\Options::OPT_KEY ?>[local_cache]"
                                            id="local_cache"
                                            <?php echo (in_array($srv_cls, org\Options::get('cleaners', [])) ? "" : "disabled") ?>
                                        ><?php echo htmlspecialchars(trim(org\Options::get("local_cache", ''))) ?></textarea>
                                    </p>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                    <span>
                        <h3>
                            Daemon:
                        </h3>
                        <b>Version: </b>
                        <select name="<?php echo org\Options::OPT_KEY ?>[daemon_version]">
                            <option
                                value="Develop"
                                <?php echo org\Options::get('daemon_version', 'Master') == 'Develop' ? "selected" : "" ?>
                                >
                                Develop
                            </option>
                            <option
                                value="Release"
                                <?php echo org\Options::get('daemon_version', 'Master') == 'Release' ? "selected" : "" ?>
                                >
                                Release
                            </option>
                            <option
                                value="Master"
                                <?php echo org\Options::get('daemon_version', 'Master') == 'Master' ? "selected" : "" ?>
                                >
                                Master
                            </option>
                        </select>
                    </span>
                    <?php if (org\Options::get('is_debug')) : ?>
                    <div>
                        <h3>Application key:</h3>
                        <?php echo org\Options::getApplicationKey() ?>
                    </div>
                    <?php endif ?>
                </div>
                <div id="extra_content" class="tab-content <?php echo org\Options::get('plugin_advanced_mode', null) == 1? '' : 'hidden'?>">
                    <h2>Extra Content</h2>
                    <div class="important-setting">
                        <input
                            type="checkbox"
                            name="<?php echo org\Options::OPT_KEY ?>[extra_content]"
                            id="extra"
                            value="1"
                            <?php echo org\Options::get("extra_content", 0) == 1? "checked" : "" ?>
                            >
                        <label for="extra">
                            Collect Extra Content
                        </label>
                    </div>
                    <br/>
                    <div class="important-setting">
                        <input
                            type="text"
                            name="<?php echo org\Options::OPT_KEY ?>[co_author]"
                            id="co_author"
                            value="<?php echo org\Options::get("co_author", '')?>"
                            >
                        <label for="co_author">
                            Co author custom field name
                        </label>
                    </div>
                    <?php foreach (ExtraContent::create()->getServices() as $serv_name):
                        try {
                            $serv = ExtraContentServiceFactory::create($serv_name);
                        } catch (Exception $e) {
                            // silence is golden
                        }
                     ?>
                    <table class="extra_table">
                        <tr>
                            <td>
                                <h3><?php echo $serv::getLabel() ?></h3>
                                <div>
                                    <label>

                                        <h4><input type="checkbox" class="check_all"> All </h4>
                                    </label>
                                </div>
                            </td>
                        </tr>
                        <tr>
                        <td class="extra_choice">
                            <div class="flex_wrapper">

                                <?php foreach ($serv->getExtraContentSource() as $src_name => $src_value) :
                                    if (empty($serv->getOptionsContainer())) {
                                        continue ;
                                    }
                                ?>
                                <div class="extra_choice_item">
                                    <label>
                                        <input
                                            type="checkbox"
                                            id="clickio_<?php echo $src_name ?>"
                                            name="<?php echo org\Options::OPT_KEY?>[<?php echo $serv->getOptionsContainer()?>][]"
                                            value="<?php echo $src_name ?>"
                                            <?php echo (in_array($src_name, org\Options::get($serv->getOptionsContainer()))? "checked": '') ?>
                                            >
                                        <div class="label-text" title="<?php echo $src_name ?>"><?php echo $src_value ?></div>
                                    </label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </td>
                        </tr>
                        <?php if ($serv->getName() == 'hooks'): ?>
                        <tr>
                            <td>
                                <div>
                                    <h4>Custom hooks</h4>
                                </div>
                                <input
                                    type="text"
                                    id="clickio_extra_custom_actions"
                                    name="<?php echo org\Options::OPT_KEY?>[extra_custom_actions]"
                                    value="<?php echo org\Options::get('extra_custom_actions') ?>"
                                >
                                <div>
                                    <small>Specify hooks as comma separated list(csv)</small>
                                </div>
                            </td>
                        </tr>
                        <?php endif ?>
                    </table>
                    <?php endforeach; ?>
                </div>
            </div>

            <?php submit_button(); ?>
        </form>
    </div>
</div>
