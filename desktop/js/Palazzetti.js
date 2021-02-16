/* 
 *
 */

$("#table_cmd").sortable({
    axis: "y",
    cursor: "move",
    items: ".cmd",
    placeholder: "ui-state-highlight",
    tolerance: "intersect",
    forcePlaceholderSize: true
});


function addCmdToTable(_cmd) {
    if (!isset(_cmd)) {
        var _cmd = {
            configuration: {}
        };
    }
    var tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">';
    tr += '<td>';
    tr += '<div class="row">';
    tr += '<div class="col-sm-3">';
    tr += '<a class="cmdAction btn btn-default btn-sm" data-l1key="chooseIcon"><i class="fas fa-flag"></i> Icône</a>';
    tr += '<span class="cmdAttr" data-l1key="display" data-l2key="icon" style="margin-left : 10px;"></span>';
    tr += '</div>';
    tr += '<div class="col-sm-9">';
    tr += '<input class="cmdAttr form-control input-sm" data-l1key="name">';
    tr += '</div>';
    tr += '</div>';
    tr += '</td>';
    tr += '<td class="expertModeVisible">';
    tr += '<input class="cmdAttr form-control input-sm" data-l1key="id" style="display : none;">';
    tr += '<span class="type" type="' + init(_cmd.type) + '">' + jeedom.cmd.availableType() + '</span>';
    tr += '<span class="subType" subType="' + init(_cmd.subType) + '"></span>';
    tr += '</td>';
    tr += '<td>';
    tr += '<span><label class="checkbox-inline"><input type="checkbox" class="cmdAttr checkbox-inline" data-l1key="isVisible" checked/>{{Afficher}}</label></span> ';
    tr += '<span><label class="checkbox-inline"><input type="checkbox" class="cmdAttr checkbox-inline" data-l1key="isHistorized" checked/>{{Historiser}}</label></span> ';
    tr += '<span><label class="checkbox-inline"><input type="checkbox" class="cmdAttr expertModeVisible" data-l1key="display" data-l2key="invertBinary"/>{{Inverser}}</label></span> ';
	if (init(_cmd.subType) == 'numeric' || init(_cmd.subType) == 'slider'){
		tr += '<span style="display: block;">';
		tr += '<input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="minValue" placeholder="{{Min}}" title="{{Min}}" style="display:inline;width: 50px;"></input>';
		tr += '<input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="maxValue" placeholder="{{Max}}" title="{{Max}}" style="display:inline;width: 50px;"></input>';
		tr += '<input class="cmdAttr form-control input-sm" data-l1key="unite" placeholder="{{Unité}}" title="{{Unité}}" style="display:inline;width: 50px;"></input>';
		tr += '</span>';
	}
	tr += '</td>';
    tr += '<td>';
    if (is_numeric(_cmd.id)) {
        tr += '<a class="btn btn-default btn-xs cmdAction expertModeVisible" data-action="configure"><i class="fas fa-cogs"></i></a> ';
        tr += '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fas fa-rss"></i> Tester</a>';
    }
    tr += '<i class="fas fa-minus-circle pull-right cmdAction cursor" data-action="remove"></i></td>';
    tr += '</tr>';
    $('#table_cmd tbody').append(tr);
    var tr = $('#table_cmd tbody tr:last');
    jeedom.eqLogic.builSelectCmd({
        id: $(".li_eqLogic.active").attr('data-eqLogic_id'),
        filter: {
            type: 'info'
        },
        error: function (error) {
            $('#div_alert').showAlert({
                message: error.message,
                level: 'danger'
            });
        },
        success: function (result) {
            tr.setValues(_cmd, '.cmdAttr');
            jeedom.cmd.changeType(tr, init(_cmd.subType));
        }
    });
}