"use strict";
var INTEL = INTEL || {};
INTEL.helpers = INTEL.helpers || {},
INTEL.helpers.populateOptins = function() {
    $("form").each(function(n) {
        var e = $("form:eq( " + n + " )")
          , t = $(this).find("#country").val()
          , i = ["Austria", "Belgium", "Bulgaria", "Croatia", "Cyprus", "Czech Republic", "Denmark", "Estonia", "Finland", "France", "Germany", "Greece", "Hungary", "Ireland", "Italy", "Latvia", "Lithuania", "Luxembourg", "Malta", "Netherlands", "Poland", "Portugal", "Romania", "Slovakia", "Slovenia", "Spain", "Sweden", "Switzerland", "United Kingdom", "Iceland", "Liechtenstein", "Norway"];
        $(e).hasClass("optin") || ($.inArray(t, i) !== -1 ? (INTEL.helpers.optinConsent(e, !1, "No"),
        $(e).find(".opt-in-EU").show(),
        $(e).find(".opt-in-nonEU").hide(),
        INTEL.helpers.optinConsent_3rdParty(e, "show", !1, !0)) : ($(e).find(".opt-in-EU").hide(),
        $(e).find(".opt-in-nonEU").show(),
        INTEL.helpers.optinConsent(e, !0, "Yes"),
        INTEL.helpers.optinConsent_3rdParty(e, "hide", !0, !1)))
    })
}
,
$(document).ready(INTEL.helpers.populateOptins),
$("select[name=country]").change(INTEL.helpers.populateOptins),
INTEL.helpers.optinConsent = function(n, e, t) {
    $(n).find("#optinEU").prop("checked", e),
    $(n).find("#optinConsent").val(t)
}
,
INTEL.helpers.optinConsent_3rdParty = function(n, e, t, i) {
    $(n).find("#thirdPartyconsent").prop("disabled", i),
    $(n).find("#thirdPartyconsent").prop("checked", t),
    "hide" == e ? $(n).find(".opt-in-EU-3rd-party").hide() : "show" == e && $(n).find(".opt-in-EU-3rd-party").show(),
    1 == i ? $(n).find("label.opt-in-EU-3rd-party").addClass("disabled") : 0 == i && $(n).find("label.opt-in-EU-3rd-party").removeClass("disabled")
}
,
$(document).ready(function() {
    INTEL.helpers.populateOptins,
    $("input[name=optinEU]").change(function() {
        var n = $(this).closest("form");
        $(this).is(":checked") ? (INTEL.helpers.optinConsent(n, !0, "Yes"),
        INTEL.helpers.optinConsent_3rdParty(n, "show", !1, !1)) : (INTEL.helpers.optinConsent(n, !1, "No"),
        INTEL.helpers.optinConsent_3rdParty(n, "show", !1, !0))
    })
});