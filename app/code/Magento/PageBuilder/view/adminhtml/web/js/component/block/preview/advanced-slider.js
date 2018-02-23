/*eslint-disable */
define(["jquery", "Magento_PageBuilder/js/resource/slick/slick", "underscore", "./block"], function (_jquery, _slick, _underscore, _block) {
  function _inheritsLoose(subClass, superClass) { subClass.prototype = Object.create(superClass.prototype); subClass.prototype.constructor = subClass; subClass.__proto__ = superClass; }

  var AdvancedSlider =
  /*#__PURE__*/
  function (_PreviewBlock) {
    _inheritsLoose(AdvancedSlider, _PreviewBlock);

    /**
     * Assign a debounce and delay to the init of slick to ensure the DOM has updated
     *
     * @type {(() => any) & _.Cancelable}
     */

    /**
     * @param {Block} parent
     * @param {object} config
     */
    function AdvancedSlider(parent, config) {
      var _this;

      _this = _PreviewBlock.call(this, parent, config) || this;
      _this.ready = false;
      _this.element = void 0;
      _this.buildSlick = _underscore.debounce(function () {
        _underscore.delay(function () {
          if (_this.element && _this.element.children.length > 0) {
            try {
              (0, _jquery)(_this.element).slick("unslick");
            } catch (e) {// This may error
            }

            (0, _jquery)(_this.element).slick(_this.buildSlickConfig());
          }
        }, 100);
      }, 20);
      parent.children.subscribe(_this.buildSlick);

      _this.parent.stage.store.subscribe(_this.buildSlick);

      return _this;
    }
    /**
     * After child render record element
     *
     * @param {Element} element
     */


    var _proto = AdvancedSlider.prototype;

    _proto.afterChildrenRender = function afterChildrenRender(element) {
      this.element = element;
    };
    /**
     * Build the slack config object
     *
     * @returns {{autoplay: boolean; autoplaySpeed: (any | number);
     * fade: boolean; infinite: boolean; arrows: boolean; dots: boolean}}
     */


    _proto.buildSlickConfig = function buildSlickConfig() {
      return {
        arrows: this.data.show_arrows() === "1",
        autoplay: this.data.autoplay() === "1",
        autoplaySpeed: this.data.autoplay_speed(),
        dots: this.data.show_dots() === "1",
        fade: this.data.fade() === "1",
        infinite: this.data.is_infinite() === "1"
      };
    };

    return AdvancedSlider;
  }(_block);

  return AdvancedSlider;
});
//# sourceMappingURL=advanced-slider.js.map