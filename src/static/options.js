"use strict";
(function(){
// Hey stranger!
// Want some advice? Using jQuery/Lodash etc. is a bad idea


/**
 * Listen event on a list of nodes
 * @param {NodeList} list
 * @param {string} event
 * @param {function(): any} fn
 */
function addEventListenerList(list, event, fn) {
    for (var i = 0, len = list.length; i < len; i++) {
        list[i].addEventListener(event, fn, false);
    }
}

/**
 * Create Tabs
 * @class
 * @param {Object} params custom params
 * @param {string} params.el selector to root element
 * @param {string} params.tabs selector to tabs list
 */
const Tabs = function(params){
    Object.assign(this.params, params);
    this.init();
}

/**
 * Defaults
 */
Tabs.prototype = Object.create({
    params: {
        el: null,
        tabs: null
    }
})
Tabs.prototype.constructor = Tabs

/**
 * Init object props, add listners.
 * Fires after constructor.
 */
Tabs.prototype.init = function(){
    this.el = document.querySelector(this.params.el);
    this.tabs = this.el.querySelectorAll(this.params.tabs);

    addEventListenerList(this.tabs, 'click', this.toggle_tab.bind(this));
}

/**
 * Toggle tabs
 *
 * @param {MouseEvent} e
 */
Tabs.prototype.toggle_tab = function(e){
    e.preventDefault();

    const target_tab = this.el.querySelector(e.target.hash)

    for(let i = 0, len = this.tabs.length; i < len; i++){
        let tab = this.tabs[i]
        let trgt = this.el.querySelector(tab.hash)
        // trgt.style.display = 'none'
        trgt.classList.remove('active')
        tab.parentElement.classList.remove('ui-state-active')
    }

    target_tab.classList.add('active')
    e.target.parentElement.classList.add('ui-state-active')
}

/**
 * @class
 * @classdesc Main class
 */
const OptionsPage = function() {
    document.addEventListener("DOMContentLoaded", function(e){
        this.init();
    }.bind(this))
}
/**
 * Defaults
 */
OptionsPage.prototype = Object.create({

})
OptionsPage.prototype.constructor = OptionsPage;

/**
 * Initiate page
 * Fires when document is ready
 */
OptionsPage.prototype.init = function(){
    this.el = document.querySelector("#clickio-prism-wp-plugin #cl-settings")
    if(!this.el){
        console.error("Unable to find cl-settings item.");
        return ;
    }

    let nginx_cleaner = this.el.querySelector('.cleaners-list li.NginxLocal input');
    if(nginx_cleaner){
        nginx_cleaner.addEventListener('change', this.toggle_nginx.bind(this))
    }

    let confirm = this.el.querySelectorAll('input[type="checkbox"][data-confirm="1"]');
    if(confirm.length){
        addEventListenerList(confirm, 'change', this.confirm);
    }

    let all_extra = this.el.querySelectorAll('input[type="checkbox"].check_all');
    if(all_extra.length){
        addEventListenerList(all_extra, 'click', this.checkAllExtra);
    }

    this.tabs = new Tabs({
        el: "#tabs",
        tabs: 'ul.nav-tab-wrapper .nav-tab a'
    });

    // prevent FUOC
    this.el.style.display = 'block';
}

/**
 * Toggle "Local cache location" on Nginx local cache cleaner
 *
 * @param {MouseEvent} e
 */
OptionsPage.prototype.toggle_nginx = function(e){
    let is_active = e.target.checked
    let cache_location = this.el.querySelector(".cache_location")

    if(is_active){
        cache_location.classList.remove('invisible')
        cache_location.querySelector('textarea').disabled = false;

    } else {
        cache_location.classList.add('invisible')
        cache_location.querySelector('textarea').disabled = true;
    }
}

OptionsPage.prototype.confirm = function(e){
    const input = e.target;

    // this will fire when option turn off
    if(!input.checked){
        return ;
    }
    let answer = confirm("Are you sure?");
    input.checked = answer;
}

OptionsPage.prototype.checkAllExtra = function(e){
    let target = e.target;
    let parent = target.closest('table');
    let boxes = parent.querySelectorAll('input[type="checkbox"]');
    for (let box of boxes) {
        box.checked = target.checked;
    }
}

const clickio_opts = new OptionsPage();
})()
