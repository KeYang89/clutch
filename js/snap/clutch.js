jQuery(document).ready(function() {
    
    //Draw enrollment - point based program chart
    drawEnrollmentPointBasedChart();
    
    //Draw enrollment - punch based program chart
    drawEnrollmentPunchBasedChart();
    
});

/**
* Draw enrollment - point based program chart
* 
*/
function drawEnrollmentPointBasedChart() {
    
    jQuery(".knob").knob({
        draw : function () {

            // "tron" case
            if(this.$.data('skin') == 'tron') {

                var a = this.angle(this.cv)  // Angle
                , sa = this.startAngle          // Previous start angle
                , sat = this.startAngle         // Start angle
                , ea                            // Previous end angle
                , eat = sat + a                 // End angle
                , r = true;

                this.g.lineWidth = this.lineWidth;

                this.o.cursor
                && (sat = eat - 0.3)
                && (eat = eat + 0.3);

                if (this.o.displayPrevious) {
                    ea = this.startAngle + this.angle(this.value);
                    this.o.cursor
                    && (sa = ea - 0.3)
                    && (ea = ea + 0.3);
                    this.g.beginPath();
                    this.g.strokeStyle = this.previousColor;
                    this.g.arc(this.xy, this.xy, this.radius - this.lineWidth, sa, ea, false);
                    this.g.stroke();
                }

                this.g.beginPath();
                this.g.strokeStyle = r ? this.o.fgColor : this.fgColor ;
                this.g.arc(this.xy, this.xy, this.radius - this.lineWidth, sat, eat, false);
                this.g.stroke();

                this.g.lineWidth = 2;
                this.g.beginPath();
                this.g.strokeStyle = this.o.fgColor;
                this.g.arc(this.xy, this.xy, this.radius - this.lineWidth + 1 + this.lineWidth * 2 / 3, 0, 2 * Math.PI, false);
                this.g.stroke();

                return false;
            }
        }
    });
}

//Draw enrollment - punch based program chart
function drawEnrollmentPunchBasedChart() {
    
    jQuery.fn.raty.defaults.hints= [];
    jQuery.fn.raty.defaults.numberMax= 500;
    
    
    if (jQuery('.punch-raty-box').length > 0) {
        jQuery('.punch-raty-box').each(function() {
            var _num = jQuery(this).attr('data-number');
            var _score = jQuery(this).attr('data-score');
            jQuery(this).raty({ readOnly:true, number: _num, score:_score });
        });
    }
    
}