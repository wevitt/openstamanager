function doTooltipCalc(element) {
    let $element = $('#' + element);
    let input = $('#calcExpression').val();
    let result = math.evaluate(input);

    // using a regex, return only the numeric part of result
    result = result.toString().replace(/[^0-9\-.]/g, '');

    $element.val(result).trigger('keyup');
    $element.prev('.fa-calculator').tooltipster('close');
}

function initTooltipCalc() {
    $('input.number-input').each(function() {
        let $input = $(this);
        let inputId = $input.attr('id');
        let $icon = null;

        if ($input.hasClass('with-calculator') || $input.prop('disabled') === true || $input.prop('readonly') === true) {
            return;
        }

        // wrap the input with a div
        // if the input's parent is a div, add the class position-relative to it
        if ($input.parent().is('div') || $input.parent().is('td')) {
            $input.parent().addClass('position-relative text-left');
        } else {
            $input.wrap('<div class="position-relative"></div>');
        }

        $container = $input.parent();

        $input.before('<i class="fa fa-calculator" style="position:absolute;cursor: pointer;z-index: 999;padding: 10px;"></i>');
        $input.addClass('with-calculator');
        $icon = $container.find('.fa-calculator');

        $icon.tooltipster({
            content: '<span for="calcExpression" style="display:flex;">' +
                '<input type="text" class="form-control mr-1" id="calcExpression" placeholder="Es. 100-12%">' +
                '<button type="button" id="calcButton" class="btn btn-primary btn-sm ml-1">' +
                '<i class="fa fa-calculator"></i>' +
                '</button>' +
                '</span>',
            contentAsHTML: true,
            trigger: 'click',
            interactive: true,
            touchDevices: true,
            functionReady: function(instance, helper) {
                $('#calcExpression').on('paste', function(e) {
                    e.preventDefault();
                    var value = e.originalEvent.clipboardData.getData('text');

                    $('#calcExpression').val(value.replace(',', '.'));
                    doTooltipCalc(inputId);
                });
                $('#calcExpression').on('keypress', function(event) {
                    if (event.which == 13) {
                        event.preventDefault();
                        doTooltipCalc(inputId);
                    } else {
                        // replace comma with dot
                        if (event.which == 44) {
                            event.preventDefault();
                            $('#calcExpression').val($('#calcExpression').val() + '.');
                        }
                    }
                });
                $('#calcButton').on('click', function() {
                    doTooltipCalc(inputId);
                });
            }
        });
    });
}

$(function() {
    initTooltipCalc();
});
$(document).on('shown.bs.modal', function() {
    initTooltipCalc();
});
