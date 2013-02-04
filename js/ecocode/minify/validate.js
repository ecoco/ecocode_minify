var _ecocodeMinify = {
	tab: Class.create(varienTabs,{
	    showTabContent : function($super, tab) {
	        var tabContentElement = $(this.getTabContentElementId(tab));
	        if (!tabContentElement) return;
	        
            if (this.activeTab == tab) {
	           tabContentElement.toggle();
				if(tabContentElement.getStyle('display') == 'block'){
          			tab.addClassName('active');
				} else {
          			tab.removeClassName('active');
				}
            } else {
				$super(tab);
			}
	    }
	}),
		
	process: Class.create({
		initialize: function(options){
			options = options || {};
			var defaultOptions = {
				maxInstCount: 2,
				process: Prototype.emptyFunction,
				onStart: Prototype.emptyFunction,
				onSuccess: Prototype.emptyFunction,
				onError: Prototype.emptyFunction,
				onProcessStart: Prototype.emptyFunction,
				onProcessFinish: Prototype.emptyFunction
			};
			this.options = Object.extend(defaultOptions, options);
			this.setup();
		},

		setup: function(){
			this.instCount = 0;
			this.queue = new Array();
		},

		start: function(){
			this.checkHealth();
		},

		stop: function(){
			this.queue = new Array();
		},

		pause: function(){
			this.instCount = 0;
		},

		resum: function(){

		},

		getInstCount: function(){
			return this.instCount;
		},

		addToQueue: function(item, start){
			if(Object.isArray(item)){
				this.queue.push.apply(this.queue, item);
			}else{
				this.queue.push(item);
			}
			if(start) this.checkHealth();
		},

		clearQueue: function(){
			this.queue = new Array();
		},

		checkHealth: function(){
			if(!this.canStartNew()) return;
			
			if(this.queue.size() > 0){
				while(this.instCount < this.options.maxInstCount && this.queue.size() > 0){
					this.startInst();
				}
			} else {
				if(this.running){
					this.options.onProcessFinish();
				}
				this.running = false;
			}
		},
		
		canStartNew: function(){
			return (this.instCount < this.options.maxInstCount);
		},

		startInst: function(){
			if(!this.running){
				this.running = true;
				this.options.onProcessStart();
			}
			if(!this.queue.size()) return;
			var data = this.queue.shift();
			if(data.deleted) return this.startInst();
			
			this.onStart();
			try{
				this.options.process(data, function(){
					this.onSuccess();
				}.bind(this));
			} catch(e){
				this.onError();
			}
		},

		onStart: function(){
			this.instCount++;
			this.options.onStart();
		},

		onSuccess: function(){
			this.instCount--;
			this.checkHealth();
			this.options.onSuccess();
		},
		onError: function(){
			this.instCount--;
			this.checkHealth();
			this.options.onError();
		}
	}),
		
	
	splitSpan: Class.create({
		initialize: function(options){
			options = options || {};
			var defaultOptions = {
				holder: 'splitspan-holder',
				handles: ['.col-resize-handle'], //css #id or .class
				cols: ['.splitspan'], //css #id or .class
				handleWidth: 10,
				colMinWidht: 10
			};
			this.options = Object.extend(defaultOptions, options);
			this.setup();
		},
		
		setup: function(){
			this.holder = $(this.options.holder);
			this.handles = this.holder.select(this.options.handles.join(','));
			this.cols = this.holder.select(this.options.cols.join(','));
			this.setSizes();
			this.setObserver();
		},
		
		setObserver: function(){
			Event.observe(window, 'load', function(){ this.setSizes(); }.bind(this)); 
			Event.observe(window, 'resize', function(event){ this.adjustSize(); }.bind(this)); 
			this.handles.each(function(handle){
				new Draggable(handle, {
					constraint: 'horizontal',
					onStart : function(handle, event) {this.resize(handle, event);}.bind(this),	
					onDrag : function(handle, event) {this.resize(handle, event);}.bind(this),	
					onEnd : function(handle, event) {this.resize(handle, event);}.bind(this)
				});
			}.bind(this));			
		},
		
		adjustSize: function(){
			var newWidth = this.holder.getWidth() - (this.handles.size() * this.options.handleWidth);
			var oldWidth = 0;
			this.holder.select(this.options.cols.concat(this.options.handles).join(',')).each(function(el){
				oldWidth += el.getWidth();
			});
			var diff = (newWidth - oldWidth) / this.cols.size();
			this.cols.each(function(col){
				col.setStyle({width: col.getWidth() + diff + 'px'});
			});
			var left = 0;
			this.holder.select(this.options.cols.concat(this.options.handles).join(',')).each(function(el){
				el.setStyle({left: left + 'px'});
				left += el.getWidth();
			});
			this.calcHolderHeight();
			this.resizeSourceCode();
			this.resizeOutputContent();
		},

		resize: function(handle, event){
			var width = this.holder.getWidth() - (this.handles.size() * this.options.handleWidth);
			var left = parseFloat(handle.element.getStyle('left'));
			$('col-input').setStyle({
				left: '0px',
				width: left + 'px'
			});
			$('col-output').setStyle({
				left: left + this.options.handleWidth +'px',
				width: width - left + 'px'
			});
			this.resizeSourceCode();
			this.resizeOutputContent();
		},
		
		setSizes: function(){
			this.calcHolderHeight();
			var width = this.holder.getWidth();
			
			width -= this.handles.size() * this.options.handleWidth;
			this.handles.each(function(handle){
				handle.setStyle({width: this.options.handleWidth + 'px'});
			}.bind(this));
			var colsWithoutSize = new Array();

			this.cols.each(function(col){
				if(!col.style.width){
					colsWithoutSize.push(col);
				} else {
					width -= col.getWidth();
				}
			});
			var defaultColsSize = width / colsWithoutSize.size();
			colsWithoutSize.each(function(col){
				col.setStyle({width: Math.floor(defaultColsSize) + 'px'});
			});
			var left = 0;
			this.holder.select(this.options.cols.concat(this.options.handles).join(',')).each(function(el){
				el.setStyle({'left': left + 'px'});
				left += el.getWidth();
			});
			this.resizeSourceCode();
			this.resizeOutputContent();
		},

		calcHolderHeight: function(){
			//we do the easy dirty way
			var attempt = 0;
			var maxHeight = document.viewport.getHeight() - $(document.body).down('.wrapper').getHeight();
			while(attempt < 5){
				this.holder.setStyle({height: this.holder.getHeight() + maxHeight + 'px'});		
				attempt++;
				maxHeight = document.viewport.getHeight() - $(document.body).down('.wrapper').getHeight();		
			}
			return maxHeight;
		},
		resizeSourceCode: function(){
			$('source-code').setStyle({
				height: $('col-input').getHeight() - $('col-input').down('.compiler-options').getHeight() - 52 + 'px'
			});
		},
		
		resizeOutputContent: function(){
			var outputHeader = $('col-output').down('.output-header');
			$('col-output').down('.output-container').setStyle({
				'height': outputHeader.parentNode.getHeight() - outputHeader.getHeight() - 10 + 'px'
			});					
		}
	}),
	
	validator: Class.create({
		initialize: function(options){
			options = options || {};
			var defaultOptions = {
			};
			this.options = Object.extend(defaultOptions, options);
			this.setup();
			this.splitSpan = new _ecocodeMinify.splitSpan();
		},

		setup: function(){
			this.files = [];
			this.process = new _ecocodeMinify.process({
				process: function(data, cb){
					this.compileFile(data, cb);
				}.bind(this),
				onStart: function(){ this.updateProcessCount();}.bind(this),
				onSuccess: function(){ this.updateProcessCount(); this.incStatusCount('processed');}.bind(this),
				onError: function(){ this.updateProcessCount();	this.incStatusCount('processed');}.bind(this),
				onProcessStart: function(){
					$('compiling-start').hide();
					$('compiling-stop').show();					
				}.bind(this),
				onProcessFinish: function(){
					$('compiling-start').show();
					$('compiling-stop').hide();			
				}.bind(this)
			});
			this.setObserver();
		},

		setObserver: function(){
			$('get-js-files').observe('click', function(){this.getFilePaths();}.bind(this));
			$('compiling-start').observe('click', function(){this.startCompiling();}.bind(this));
			$('compiling-stop').observe('click', function(){this.stopCompiling();}.bind(this));

			var tabs = $('result_tab').select('.tab-item-link');
			tabs.each(function(tab){
				var key = tab.readAttribute('data-key');
				tab.observe('click', function(){
				tabs.each(function(el){ el.removeClassName('active'); });
					tab.addClassName('active');
					$('js-files').writeAttribute('data-key', key);
				});
			});
			this.obOptions();
			this.obAddFile();
			this.obCustom();
			$('clear-all').observe('click', function(){
				this.clear();
			}.bind(this)); 
		},
		
		obOptions: function(){
			var option = $('compiling-options').down('select[name=option]');
			$('splitspan-holder').writeAttribute('data-option', option.getValue());
			option.observe('change', function(){
				$('splitspan-holder').writeAttribute('data-option', option.getValue());
				this.splitSpan.resizeSourceCode();
			}.bind(this));			
		},
		
		obCustom: function(){
			$('compile-custom').observe('click', function(){
				this.compileCustom();
			}.bind(this));
		},
		
		obAddFile: function(){
			$('add-js-file').observe('click', function(){
				var filePath = $('file_path').getValue();
				if(!filePath) return false;
				this.checkFilePath(filePath);
			}.bind(this));
		},
		
		clear: function(){
			this.files = new Array();
			this.stopCompiling();
			this.resetTotals();
			$('js-files').innerHTML = '';
			$('total-fileCount').innerHTML = '';			
		},
		
		checkFilePath: function(path){
			this.runfunc('checkFilePath', {path: path}, {
				onSuccess: function(jsondata){
					$('col-output').writeAttribute('data-type', 'file');
					this.files.push.apply(this.files, jsondata.files);
					this.renderFiles();
				}.bind(this)
			});			
		},
		
		compileCustom: function(){
			this.clear();
			var params = this.collectData();
			this.runfunc('compileCustom', params, {
				onSuccess: function(jsondata){
					this.files.push.apply(this.files, jsondata.files);
					this.renderFiles();
					this.updateFileInfo(this.files[0], jsondata);
					$('col-output').writeAttribute('data-type', 'file');
				}.bind(this)
			});				
		},
		
		runfunc: function(callname, params, options){
			params = params || {};
			params.callname = callname;
			options = options || {};
			var defaultOptions = {
				onSuccess: Prototype.emptyFunction,
				onError: Prototype.emptyFunction
			};
			options = Object.extend(defaultOptions, options);
			new Ajax.Request(ajaxRunfuncUrl, {
				parameters: params,
				onSuccess: function(response){
					try{
						var jsondata = response.responseJSON;
						if(jsondata && jsondata.status){
							options.onSuccess(jsondata);
						} else {
							options.onError(jsondata);
						}
					} catch(e){
						console.log(e);
					}
				},
				onError: function(){
					options.onError();
				}
			});				
		},
		
		getFilePaths: function(){
			$('col-output').writeAttribute('data-type', 'none');
			var params = this.collectData();
			this.runfunc('getJsDir', params, {
				onSuccess: function(jsondata){
					$('col-output').writeAttribute('data-type', 'file');
					this.renderFiles(jsondata.files);
				}.bind(this)
			});
		},

		resetTotals: function(){
			$$('span[class^="total-"]').each(function(el){
				el.innerHTML = 0;
			});
		},

		incStatusCount: function(status){
			if(!$('total-' + status)) return;
			$('total-' + status).innerHTML = parseInt($('total-' + status).innerHTML) + 1;
		},
		
		decStatusCount: function(status){
			if(!$('total-' + status)) return;
			var count = parseInt($('total-' + status).innerHTML) - 1;
			if(count < 0) count = 0;
			$('total-' + status).innerHTML = count;
		},
		
		startCompiling: function(){
			this.resetTotals();	
			$('col-output').removeClassName('compiling');
			$('loading-mask').addClassName('eco-hidden');
			if(!this.files.size()) return;
			$('col-output').addClassName('compiling');

			this.baseParams = this.collectData();
			this.process.addToQueue(this.files.clone());
			this.process.start();
		},

		updateProcessCount: function(){
			$('total-process').innerHTML = this.process.getInstCount();
		},
		
		stopCompiling: function(){
			$('loading-mask').removeClassName('eco-hidden');
			$('compiling-start').show();
			$('compiling-stop').hide();
			this.process.stop();
		},

		compileFile: function(file, cb){
			if(file.deleted) return;
			if(!$('js-file-' + file.id)) return cb();
			var params = Object.clone(this.baseParams);
			params.file = file.path;
			this.setFileStatus(file.id, 'processing');
			
			this.runfunc('compile', params, {
				onSuccess: function(jsondata){
					this.updateFileInfo(file, jsondata);
					cb();
				}.bind(this),
				onError: function(jsondata){
					this.setFileStatus(file.id, 'error');
					cb();
				}.bind(this)
			});
		},

		setFileStatus: function(id, status){
			if(!$('js-file-' + id)) return;
			$('js-file-' + id).writeAttribute('data-status', status);
			$('js-file-' + id).down('.file-status').innerHTML = status;
		},

		updateFileInfo: function(file, data){
			var holder = $('js-file-' + file.id);
			if(!holder) return;

			if(data.errors.size() > 0){
				file.status = 'error';
			} else if(data.warnings.size() > 0){
				file.status = 'warning';
			} else {
				file.status = 'success';
			}
			this.incStatusCount(file.status);
			this.setFileStatus(file.id, file.status);
			holder.down('td.compiled-size').innerHTML = data.size + 'KB';				
			holder.down('td.compiling-time').innerHTML = data.compiling_time + ' Sec';
			holder.down('td.compression-rate').innerHTML = ((1 - (data.size / file.size)) * 100).toFixed(2) + '%';
			$('js' + file.id + '_tab_code_content').down('code').innerHTML = data.compressed_code;
			data.tab_content.each(function(tabInfo){
				$('js' + file.id + '_tab_' + tabInfo.tab + '_content').innerHTML = tabInfo.html;
				holder.down('span.' + tabInfo.key + '-count').innerHTML = data[tabInfo.key].size();
			});
		},
		
		collectData: function(){
			var data = {};
			$$('#col-input .data').each(function(el){
				data[el.name] = el.getValue();
			});
			return data;
		},
					
		renderFiles: function(files){
			if(files){
				this.files = files || [];
				this.resetTotals();
			}
			var jsFileList = $('js-files');

			$('compiling-start').hide();
			$('compiling-stop').hide();
			if(this.files.size() > 0) {
				$('compiling-start').show();
			}
			
			$('total-fileCount').innerHTML = this.files.size();
			jsFileList.innerHTML = '';
			var jsFilefTemplate = new Template($('validate-js-file-template').innerHTML);
			this.files.each(function(file, index){
				file.id = index + 1;
				var li;
				var data = {
					id: file.id,
					status: 'waiting',
					size: file.size,
					path: file.path
				};
				jsFileList.appendChild(li = Builder.node('li'));
				li.innerHTML = jsFilefTemplate.evaluate(data);
				li.down('.action.file-reload').observe('click', function(){
					this.setFileStatus(file.id, 'waiting');
					this.decStatusCount('processed');
					this.decStatusCount(file.status);
					this.process.addToQueue(file, true);
				}.bind(this));
				li.down('.action.file-remove').observe('click', function(){
					file.deleted = true;
					this.decStatusCount('fileCount');
					li.remove();
				}.bind(this));
				new _ecocodeMinify.tab('js' + file.id + '_tab', 'js' + file.id + '_tab_content');
				
			}.bind(this));
		}
	})
};