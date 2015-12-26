
function launchPropelSOA(propelSOAApp, debugMode, domain, customSOAPath, alternateInclude)
{
    // the default value function isn't loaded yet, so we will do the same operation by hands
    if (typeof(alternateInclude) == "undefined")
    {
        alternateInclude = false;
    }

    /**
     * The PropelSOAFactory class is used to create query and model objects from the attached API. These
     * javascript objects will abstract actual communication with the server side away from the angular
     * app code.
     */
    propelSOAApp.factory('PropelSOAService', function($http, $q)
    {
        function PropelSOAFactory()
        {
            var loadedClassDefinitions = {};
            this.$http = $http;
            this.$q    = $q;

            this.registeredObjects = {};
            this.registeredCollections = {};

            /**
             * This method will check one of our internal caches to see if a particular map is already defined.
             * 
             * @param cacheName
             * @param namespace
             * @param bundle
             * @param entity
             */
            this.testInternalCachePath = function(cacheName, namespace, bundle, entity)
            {
                if (!objectHasKey(this[cacheName], namespace))
                {
                    return false;
                }
                if (!objectHasKey(this[cacheName][namespace], bundle))
                {
                    return false;
                }
                if (!objectHasKey(this[cacheName][namespace][bundle], entity))
                {
                    return false;
                }

                return true;
            };


            /**
             * This method will make sure our internal cache has a path defined for us to add objects to. If the
             * path is already defined, this will have no effect.
             * 
             * @param cacheName
             * @param namespace
             * @param bundle
             * @param entity
             */
            this.setupInternalCachePath = function(cacheName, namespace, bundle, entity)
            {
                var finalEntryIsArray = cacheName == "registeredCollections";

                if (!objectHasKey(this[cacheName], namespace))
                {
                    this[cacheName][namespace] = {};
                }
                if (!objectHasKey(this[cacheName][namespace], bundle))
                {
                    this[cacheName][namespace][bundle] = {};
                }
                if (!objectHasKey(this[cacheName][namespace][bundle], entity))
                {
                    this[cacheName][namespace][bundle][entity] = finalEntryIsArray ? [] : {};
                }
            };


            /**
             * This method will test to see if a particular object is already cached and return the 
             * object if we have it.
             * 
             * @param namespace
             * @param bundle
             * @param entity
             * @param recordId
             * 
             * @return PropelSOAObject
             */
            this.getRegisteredObject = function(namespace, bundle, entity, recordId)
            {
                if (!this.testInternalCachePath('registeredObjects', namespace, bundle, entity))
                {
                    return null;
                }

                if (!objectHasKey(this.registeredObjects[namespace][bundle][entity], recordId))
                {
                    return null;
                }

                return this.registeredObjects[namespace][bundle][entity][recordId];
            };


            /**
             * This method will register a model with our service. This will help to make sure that any model 
             * that is supposed to be the "same" record will get the same object. This will help propagate 
             * changes throughout the entire application.
             * 
             * @param object
             */
            this.registerObject = function(object)
            {
                primaryKeyFieldName = object.getPkName();
                recordId = object.model[primaryKeyFieldName];
                
                if (!recordId)
                {
                    return;
                }

                this.setupInternalCachePath('registeredObjects', object.namespace, object.bundle, object.entity);                
                this.registeredObjects[object.namespace][object.bundle][object.entity][recordId] = object;
            };


            /**
             * This method will take a collection and store it in our cache, so that when records are deleted we can
             * find them and remove them from any angular collections.
             * 
             * @param collection
             * @param namespace
             * @param bundle
             * @param entity
             */
            this.registerCollection = function(collection, namespace, bundle, entity)
            {
                this.setupInternalCachePath('registeredCollections', namespace, bundle, entity);
                this.registeredCollections[namespace][bundle][entity].push(collection);
            };


            /**
             * This method will comb through our registered collections and remove any objects that have been deleted.
             * 
             * @param namespace
             * @param bundle
             * @param entity
             * @param recordId
             */
            this.removeDeletedRecordsFromRegisteredCollections = function(namespace, bundle, entity, recordId)
            {
                if (!this.testInternalCachePath('registeredCollections', namespace, bundle, entity))
                {
                    // this means that no collections have been registered, so there is nothing to search through
                    return;
                }

                var collectionsCount = this.registeredCollections[namespace][bundle][entity].length;
                for (var index = 0; index < collectionsCount; ++ index)
                {
                    var collection = this.registeredCollections[namespace][bundle][entity][index];
                    var recordsCount = collection.collection.length;

                    for (var recordIndex = 0; recordIndex < recordsCount; ++ recordIndex)
                    {
                        var object = collection.collection[recordIndex];
                        if (recordId == object.getPk())
                        {
                            // remove the identified item from the collection because it has already been deleted

                            collection.collection.splice(recordIndex, 1);
                            break;
                        }
                    }
                }
            };


            /**
             * This is the main factory method for getting generated objects. Calling this method 
             * will get an API client for the specified application class. Also, provide the recordId
             * if possible to make sure that you are pulling registered objects when one is available.
             */
            this.getNewObject = function(namespace, bundle, entity, recordId)
            {
                if (typeof(recordId) != "undefined")
                {
                    registeredObject = this.getRegisteredObject(namespace, bundle, entity, recordId);

                    if (registeredObject)
                    {
                        return registeredObject;
                    }
                }

                return this.loadGeneratedObject('Object', namespace, bundle, entity);
            };


            /**
             * This is the main factory method for query building objects. Calling this
             * method will get one of our query builders.
             */
            this.getQuery = function(namespace, bundle, entity)
            {
                return this.loadGeneratedObject('Query', namespace, bundle, entity);
            };


            /**
             * This is the main factory method for collection objects. Calling this method
             * will get a new, empty collection.
             */
            this.getCollection = function(namespace, bundle, entity)
            {
                return this.loadGeneratedObject('Collection', namespace, bundle, entity);
            };


            /**
             * This method will build a generated object, and if need be import the class definition
             * from the server-side controller.
             */
            this.loadGeneratedObject = function(classType, namespace, bundle, entity)
            {
                if(!this.isClassDefinitionLoaded(classType, namespace, bundle, entity))
                {
                    this.importGeneratedObjectClassDefinition(classType, namespace, bundle, entity);
                }

                className = namespace + bundle + 'Bundle' + entity + classType;

                newObject = new window[className]();
                newObject.propelSOA = this;

                return newObject;
            };


            /**
             * This method determines if a given class definition has already been loaded.
             */
            this.isClassDefinitionLoaded = function(classType, namespace, bundle, entity)
            {
                if (!objectHasKey(loadedClassDefinitions, namespace)) 
                {
                    return false;
                }
                
                if (!objectHasKey(loadedClassDefinitions[namespace], bundle)) 
                {
                    return false;
                }

                if (!objectHasKey(loadedClassDefinitions[namespace][bundle], entity)) 
                {
                    return false;
                }


                return objectHasKey(loadedClassDefinitions[namespace][bundle][entity], classType);
            };


            /**
             * This is the method that will pull down a generated object from the GeneratedObject controller.
             */
            this.importGeneratedObjectClassDefinition = function(classType, namespace, bundle, entity)
            {
                // most of the time the classType should be capitalized already, but we'll adjust 
                // for that for the cases where it won't be

                var loadFunction = 'propelSOALoad' + classType.charAt(0).toUpperCase() + classType.slice(1) +
                    '_' + namespace + '_' + bundle + '_' + entity + '();';


                // the object classType can have a two-stage loading process so that the generated object
                // can be re-generated and changed easily without damaging custom content added. in this
                // case we need to load the generated "partial object" first, and then load the real object
                // that will extend the generated version

                /* jshint evil: true */
                if (classType == 'Object')
                {
                    var loadPartialFunction = loadFunction.replace('propelSOALoad', 'propelSOALoadPartial');
                    var loadPartialFunctionName = loadPartialFunction.replace('();', '');

                    if (typeof(window[loadPartialFunctionName]) == 'function')
                    {
                        eval(loadPartialFunction);
                    }
                }
                eval(loadFunction);
                /* jshint evil: false */

                // mark this class definition as loaded so we do not load it again and cause conflicts

                if (!objectHasKey(loadedClassDefinitions, namespace)) 
                {
                    loadedClassDefinitions[namespace] = {};
                }
                
                if (!objectHasKey(loadedClassDefinitions[namespace], bundle)) 
                {
                    loadedClassDefinitions[namespace][bundle] = {};
                }

                if (!objectHasKey(loadedClassDefinitions[namespace][bundle], entity)) 
                {
                    loadedClassDefinitions[namespace][bundle][entity] = {};
                }

                loadedClassDefinitions[namespace][bundle][entity][classType] = true;
            };


            /**
             * This method will help us by getting the URL of other propelSOA classes.
             */
            this.getIncludeUrl = function(fileName)
            {
                var includeUrl = null;

                $('script').each( function()
                {
                    var srcAttribute = $(this).attr('src');

                    if (srcAttribute) 
                    {
                        var propelSOAIndex = srcAttribute.indexOf('propelsoa.js');

                        if (propelSOAIndex !== -1) 
                        {
                            includeUrl = srcAttribute.replace('propelsoa.js', fileName);
                        }
                    }
                });


                // we didn't find our own file being included, that is not good
                return includeUrl;
            };


            /**
             * This method will load another propel SOA script file. This will simulate a synchronous functionality
             * so that the loaded code can be used immediately by default.
             */
            this.include = function(fileName, async)
            {
                // default async to false
                async = typeof async !== 'undefined' ? async : false;


                // get the URL we are including
                url = this.getIncludeUrl(fileName);

                $.ajaxSetup({ async: false });
                $.getScript(url).fail(function(a,b,c){ alert(c); });

                // set the ajax back to the way it is supposed to be
                $.ajaxSetup({ async: true });
            };


            // include all of the needed files
            if (!alternateInclude)
            {
                this.include('class.js');
                this.include('propelsoahelper.js');

                this.include('filters/basefilter.js');
                this.include('filters/equalfilter.js');
                this.include('filters/infilter.js');

                this.include('propelsoajoin.js');

                this.include('propelsoaobject.js');
                this.include('propelsoaquery.js');
                this.include('propelsoacollection.js');
            }
        }

        propelSOA = new PropelSOAFactory();
        return propelSOA;
    });
}