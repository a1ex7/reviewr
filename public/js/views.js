/*
 *---------------------------------------------------
 *  Global App View
 *---------------------------------------------------
 */

App.Views.App = Backbone.View.extend({
    initialize: function() {
        console.log( this.collection.toJSON() );
    }
});

/*
 *---------------------------------------------------
 *  User View
 *---------------------------------------------------
 */

// Backbone Views for one user card

App.Views.User = Backbone.View.extend({
    model: user,
    className: "user-card",
    initialize: function(){
        this.template = _.template($('#user-card-template').html());
    },
    events: {
        'click .select-user': 'select'
    },
    select: function () {
        router.navigate('!/user/' + this.model.get("id"), true);
        return this;
    },
    render: function(){
        this.$el.html(this.template( this.model.toJSON() ));
        return this;
    }
});

// Backbone Views for all users

App.Views.UsersList = Backbone.View.extend({
    model: users,
    el: '#main-content',
    initialize: function() {
        this.model.on('sync', this.render, this);
        this.model.on('remove', this.render, this);
        this.model.on('invalid', function(error, message){
            alert(message);
        }, this);
        this.model.on('error', function (error, message) {
            alert(message.responseText);
        }, this);
    },
    render: function(){
        this.$el.html('');
        console.log('render UserList starting...');
        _.each(this.model.toArray(), function(user){
            this.$el.append( (new App.Views.User({model: user})).render().el );
            console.log('render User');
        }, this);
        console.log('render UserList end.');
        return this;
    }
});

// Backbone Views for user profile

App.Views.UserProfile = Backbone.View.extend({
    model: user,
    el: '#main-content',
    initialize: function(){
        this.template = _.template($('#user-profile-template').html());
        this.model.on('change', this.render, this);
    },
    events: {
        'click .cancel-user': 'cancel'
    },
    cancel: function () {
        router.navigate('!/users', true);
        return this;
    },
    render: function(){
        this.$el.html(this.template( this.model.toJSON() ));
        console.log('render UserProfile');
        return this;
    }
});


/*
 *---------------------------------------------------
 *  Review Request View
 *---------------------------------------------------
 */

// Backbone Views for one request card

App.Views.Request = Backbone.View.extend({
    model: request,
    className: 'col-xs-12 col-sm-6 col-md-4 request',
    initialize: function(){
        this.template = _.template($('#request-card-template').html());
        this.model.on('change', this.render, this);
    },
    events: {
        'click .request-offer-btn': 'createOffers',
        'click .request-details-btn': 'showDetails',
    },
    createOffers: function () {
        reviewers.url = 'reviewr/api/v1/user/0/offeron/' + this.model.get('id');
        reviewers.fetch({wait: true});
        return this;
    },
    showDetails: function () {
        router.navigate('!/request/' + this.model.get('id'), true);
        return this;
    },
    render: function(){
        this.$el.html(this.template( this.model.toJSON() ));
        return this;
    }
});

// Backbone Views for all review requests

App.Views.RequestsList = Backbone.View.extend({
    collection: requests,
    el: '#main-content',
    initialize: function() {
        this.collection.on('remove', this.render, this);
    },
    render: function() {
        this.$el.empty();

        var that = this;

        this.collection.fetch({
            success: function(requests, res, req) {
                if (!requests.length) {
                    console.log('Render empty view here!!');
                } else {
                    _.each(requests.models, function(rq) {
                        that.renderRequest(rq);
                        console.log('render Request');
                    });
                }
            },
            reset: true
        });
    },

    renderRequest: function(rq) {
        var requestView = new App.Views.Request({model: rq});
        this.$el.append(requestView.render().$el);
    }
});

// Backbone Views for Review Request Details

App.Views.RequestDetails = Backbone.View.extend({
    model: request,
    el: '#main-content',
    initialize: function(){
        this.template = _.template($('#review-request-details-template').html());
        this.model.on('change', this.render, this);
    },
    events: {
        'click .back-request': 'back'
    },
    back: function () {
        router.navigate('!/requests', true);
        return this;
    },
    render: function(){
        // Fetch Request Details
        this.$el.html( this.template(this.model.toJSON()) );

        // Fetch Request Author
        var author = new App.Models.User(this.model.get('user'));
        this.$el.find('.requestor').html( (new App.Views.User({model: author})).render().el);
        var reviewersBlock = this.$el.find('.reviewers');
        reviewersBlock.empty();

        // Fetch reviewers
        var req_id = this.model.get('id');
        _.each(reviewers.toArray(), function(reviewer, request_id) {
            reviewersBlock.append( (new App.Views.Reviewer({model: reviewer, request_id: req_id }) ).render().el );
        }, this);

        // Fetch Request Tags
        var request_tags_list = this.$el.find(".tags");
        request_tags_list.empty();
        _.each(request_tags.toArray(), function(request_tag) {
            request_tags_list.append( (new App.Views.Tag({model: request_tag}) ).render().el );
            console.log('render Tag');
        }, this);

        return this;
    }
});

// Backbone Views for Review Request Creation Form
App.Views.CreateRequestForm = Backbone.View.extend({
    template: _.template($('#create-request-form-template').html()),
    events: {
        'submit': 'storeRequest'
    },
    initialize: function(options) {
        this.model = options.model;
    },
    storeRequest: function(e) {
        e.preventDefault();
        
        var tags = $('.tags-input').tokenfield('getTokens');
        for (var i = 0; i < tags.length; i++) {
            tags[i]= tags[i].value;
        }

        this.model.set({
            title: $('.title-input').val(),
            details: $('.details-input').val(),
            tags: tags,
            group_id: $('input[name="group-input"]:checked').val()
        });
        this.model.save(null, {
            success: function(rq) {
                router.navigate('!/request/' + rq.get("id"), true);
            }
        });
    },
    render: function() {
        this.$el.html(this.template);
        $('.tags-input').tokenfield();
        return this;
    }
});


/*
 *---------------------------------------------------
 *  Reviewer View
 *---------------------------------------------------
 */

// Backbone Views for one reviewer small card

App.Views.Reviewer = Backbone.View.extend({
    model: reviewer,
    request_id: 0,
    className: "reviewer",
    initialize: function(options){
        this.request_id = options.request_id;
        this.template = _.template($('#reviewer-card-template').html());

    },
    events: {
        'click .accept': 'acceptOffer',
        'click .decline': 'declineOffer',
    },
    acceptOffer: function () {
        reviewers.url = 'reviewr/api/v1/user/0/accept/' + this.request_id;
        reviewers.fetch({wait: true});
        return this;
    },
    declineOffer: function () {
        reviewers.url = 'reviewr/api/v1/user/0/decline/' + this.request_id;
        reviewers.fetch({wait: true});
        return this;
    },
    render: function(){
        this.$el.html(this.template( this.model.toJSON() ));
        
        return this;
    }
});

// Backbone Views for all reviewers
// TODO: rewrite w/o sync. See requests!!!

App.Views.Reviewers = Backbone.View.extend({
    model: reviewers,
    el: '#main-content',
    initialize: function() {
        this.model.on('sync', this.render, this);
        this.model.on('remove', this.render, this);
        this.model.on('invalid', function(error, message){
            alert(message);
        },  this);
        this.model.on('error', function (error, message) {
            alert(message.responseText);
        }, this);
    },
    render: function(){
        _.each(this.model.toArray(), function(reviewer){
            this.$el.find('.reviewers').append( (new App.Views.Reviewer({model: reviewer})).render().el );
            console.log('render Reviewer');
        }, this);

        return this;
    }
});


/*
 *---------------------------------------------------
 *  Tag View
 *---------------------------------------------------
 */

 App.Views.Tag = Backbone.View.extend({
    model: tag,
    className: "tag thumbnail text-center",
    initialize: function(){
        this.template = _.template($('#tag-template').html());
    },
    render: function(){
        this.$el.html(this.template( this.model.toJSON() ));
        return this;
    }
 });


 /*
 *---------------------------------------------------
 *  Tags List View
 *---------------------------------------------------
 */

 App.Views.TagsList = Backbone.View.extend({
    model: tags,
    el: "#main-content",
    initialize: function() {
        this.model.on('sync', this.render, this);
        this.model.on('remove', this.render, this);
        this.model.on('invalid', function(error, message){
            alert(message);
        }, this);
        this.model.on('error', function (error, message) {
            alert(message.responseText);
        }, this);
    },
    render: function(){
        this.$el.html('');
        _.each(this.model.toArray(), function(tag){
            this.$el.append( (new App.Views.Tag({model: tag})).render().el );
            console.log('Tag Model Render');
        }, this);
        return this;
    }
 });