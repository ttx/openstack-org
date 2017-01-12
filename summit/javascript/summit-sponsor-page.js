// Sponsor active on scroll
$(document).ready(function () {
    $(document).on("scroll", onScroll);
    $('div.secondary-nav').secondaryNav(
        {
            num: 425 ,//number of pixels before modifying styles
            onScroll: onScroll
        }
    );
    $('[data-toggle="tooltip"]').tooltip();
});

function onScroll(event){
    var scrollPos = $(document).scrollTop();
    $('.city-nav a').each(function () {
        var currLink = $(this);
        var refElement = $(currLink.attr("href"));
        if (refElement.position().top <= scrollPos && refElement.position().top + refElement.outerHeight() > scrollPos) {
            $('.city-nav ul li a').removeClass("active");
            currLink.addClass("active");
        }
        else{
            currLink.removeClass("active");
        }
    });
}

window.onload = function(){
    var helpers = Chart.helpers;
    var attendeesRegion = new Chart(document.getElementById("attendeesRegion").getContext("2d")).Doughnut(pieDataRegion, {
        tooltipTemplate: "<%if (label){%><%=label%>: <%}%><%= value %>%",
        animateRotate: true
    });
    var attendeesRole = new Chart(document.getElementById("attendeesRole").getContext("2d")).Doughnut(pieDataRole, {
        tooltipTemplate: "<%if (label){%><%=label%>: <%}%><%= value %>%",
        animateRotate: true
    });
    var legendHolderRegion = document.createElement('div');
    var legendHolderRole = document.createElement('div');
    legendHolderRegion.innerHTML = attendeesRegion.generateLegend();
    legendHolderRole.innerHTML = attendeesRole.generateLegend();

    // Include a html legend template after the module doughnut itself
    helpers.each(legendHolderRegion.firstChild.childNodes, function (legendNode, index) {
        helpers.addEvent(legendNode, 'mouseover', function () {
            var activeSegment = attendeesRegion.segments[index];
            activeSegment.save();
            activeSegment.fillColor = activeSegment.highlightColor;
            attendeesRegion.showTooltip([activeSegment]);
            activeSegment.restore();
        });
    });
        // Include a html legend template after the module doughnut itself
    helpers.each(legendHolderRole.firstChild.childNodes, function (legendNode, index) {
        helpers.addEvent(legendNode, 'mouseover', function () {
            var activeSegment = attendeesRole.segments[index];
            activeSegment.save();
            activeSegment.fillColor = activeSegment.highlightColor;
            attendeesRole.showTooltip([activeSegment]);
            activeSegment.restore();
        });
    });
    helpers.addEvent(legendHolderRegion.firstChild, 'mouseout', function () {
        attendeesRegion.draw();
    });

    helpers.addEvent(legendHolderRole.firstChild, 'mouseout', function () {
        attendeesRole.draw();
    });

    attendeesRegion.chart.canvas.parentNode.parentNode.appendChild(legendHolderRegion.firstChild);
    attendeesRole.chart.canvas.parentNode.parentNode.appendChild(legendHolderRole.firstChild);
};
