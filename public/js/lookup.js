function Lukup(obj)
{
  this.debounceTimeout = null;
  this.request = null;
  this.url = obj.url;
  this.autofill = obj.autofill;
  this.input = $('input[name="'+obj.field+'"]');
  this.wrapper = this.input.parent();
  this.resultsContainerModel = this.wrapper.find('.autocomplete');
  this.field = this.input.attr('name');

  this.prepareMenu = function() {
    var clone = this.resultsContainerModel.clone().removeClass('model').addClass('autocomplete-temp').appendTo(this.input.parent());
    clone.show();

    return clone;
  }

  this.fillElements = function(element) {
    this.autofill.forEach(field => {
      value = $(element).attr('data-'+field);
      if (value) {
        $('input[name="'+field+'"]').val(value).addClass('border-warning');
        $('select[name="'+field+'"] option[value="'+value+'"]').prop('selected', true).parent().addClass('border-warning');
      } else {
        $('input[name="'+field+'"]').val(value).removeClass('border-warning');
        $('select[name="'+field+'"] option[value="'+value+'"]').prop('selected', true).parent().removeClass('border-warning');
      }
    });
  }

  this.reset = function() {
    $('.autocomplete-temp').remove();
  }

  this.autocomplete = function() {
    this.reset();
    
    if (this.input.val() == '')
      return;

    if (this.request)
      this.request.abort();
    
    var menu = this.prepareMenu();
    var autofill = this.autofill;

    this.request = $.post(this.url, {
        field: this.field,
        input: this.input.val()
      }, function(data, status){
        console.log('Searching with Lukup!');
        // GET RESULTS
        data.forEach(result => {
          var container = menu.find('.model').clone().removeClass('model').appendTo(menu.find('div.border'));
          container.addClass('result-temp');
          container.find('span').text(result.output);
          autofill.forEach(field => {
            container.attr('data-'+field, result[field]);
          });
        });
        // SHOW RESULTS
        menu.find('.loading strong').text('Found '+data.length+' result(s)!');
        menu.find('.result-temp').removeClass('d-none');      
    });
  };

  this.enable = function() {
    var lookup = this;

    $(lookup.wrapper).on('click', '.result-temp', function() {
      lookup.fillElements(this);
    });

    $(lookup.wrapper).on('keyup', this.input, function(event){
      clearTimeout(lookup.debounceTimeout);
      lookup.debounceTimeout = setTimeout(lookup.autocomplete(), 200);
    });

    // HIDE AUTOCOMPLETE IF CLICK ANYWHERE ON THE SCREEN
    $(document).on('click', function(e) {     
        lookup.reset();
    }); 
  }
}
