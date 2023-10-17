/**
 * Get all necessary dependencies for the block.
 */
import { __ } from '@wordpress/i18n';
import { 
    PanelBody,
    SelectControl,
    ToggleControl,
    RangeControl,
    RadioControl,
    FormTokenField
} from '@wordpress/components';
import { 
    Fragment,
    useEffect,
    useState
} from '@wordpress/element';
import { 
    useBlockProps, 
    InspectorControls,
} from '@wordpress/block-editor';
import { useSelect } from '@wordpress/data';
import ServerSideRender from '@wordpress/server-side-render';
import apiFetch from '@wordpress/api-fetch';
 
 /**
  * @return {WPElement} Element to render.
  */
 export default function edit( { attributes, setAttributes} ) {

    // get attributes
    const {
        postsPerPage,
        sort,
        categories,
        tags,
        postTypes,
        manualActive,
        manualPosts,
    } = attributes;

    const sortOptions = [
        {
            label: __('Newest to oldest', 'aubsmugg'),
            value: 'date/desc',
        },
        {
            label: __('Oldest to newest', 'aubsmugg'),
            value: 'date/asc',
        },
        {
            label: __('A → Z', 'aubsmugg'),
            value: 'title/asc',
        },
        {
            label: __('Z → A', 'aubsmugg'),
            value: 'title/desc',
        },
        {
            label: __('Random', 'aubsmugg'),
            value: 'rand',
        }
    ];

    // if Manual is active add additional sort options
    if (manualActive) {
        sortOptions.push(
            {
                label: __('Manual', 'aubsmugg'),
                value: 'post__in',
            },
        );
    }
      
    const categoriesData = useSelect((select) => {
        const cats = select('core').getEntityRecords('taxonomy', 'category', { per_page: -1 });
        return cats ? cats.map(cat => ({ id: cat.id, name: cat.name })) : [];
    }, []);

    const tagsData = useSelect((select) => {
        const tags = select('core').getEntityRecords('taxonomy', 'post_tag', { per_page: -1 });
        return tags ? tags.map(tag => ({ id: tag.id, name: tag.name })) : [];
    }, []);

    const [postTypesData, setPostTypesData] = useState({});

    const [allPosts, setAllPosts] = useState([]);

    useEffect(() => {
        apiFetch({ path: '/aubsmugg/v1/display-posts' })
            .then(data => {
                setPostTypesData(data);
                setAllPosts(data);
            })
            .catch(error => {
                console.error(error);
            });
    }, []);

    const postTypeNames = Object.keys(postTypesData);
    const allPostsData = Object.values(allPosts).flat();
    

    // Block props.
    const blockProps = useBlockProps();

    return (
        <Fragment>
            <InspectorControls>
                <PanelBody title={ __( 'Display Posts Settings' ) }>
                    <label 
                        className="blocks-base-control__label" 
                        htmlFor="sort" 
                        style={{fontSize: "0.875rem", marginBottom: "0.875rem", display: "block", fontWeight: "bold"}}
                    >
                        { __( 'Order', 'aubsmugg' ) }
                    </label>
                    <SelectControl
                        label={ __( 'Sort by', 'aubsmugg' ) }
                        value={ sort }
                        options={ sortOptions }
                        onChange={ ( value ) => {
                            console.log("Changing sort to:", value);
                            setAttributes( { sort: value } );
                            console.log("Sort is now:", sort);
                        }}
                    />
                    <hr />
                    <label 
                        className="blocks-base-control__label" 
                        htmlFor="manualActive" 
                        style={{fontSize: "0.875rem", marginBottom: "0.875rem", display: "block", fontWeight: "bold"}}
                    >
                        { __( 'Selection Type', 'aubsmugg' ) }
                    </label>
                    <ToggleControl
                        label={__("Manual Selection", "aubsmugg")}
                        checked={manualActive}
                        help = {__("If checked, you can manually select posts to display.", "aubsmugg")}
                        onChange={() => setAttributes({ manualActive: !manualActive })}
                    />
                    {!manualActive ? (
                        <>
                            <hr />
                            <label 
                                className="blocks-base-control__label" 
                                htmlFor="posts-per-page" 
                                style={{fontSize: "0.875rem", marginBottom: "0.875rem", display: "block", fontWeight: "bold"}}
                            >
                                { __( 'Posts Per Page', 'aubsmugg' ) }
                            </label>
                            <ToggleControl
                                label={ __( 'Show All Posts' ) }
                                checked={ postsPerPage === -1 }
                                onChange={ () => 
                                    setAttributes( { postsPerPage: postsPerPage === -1 ? 12 : -1 } ) 
                                }
                            />
                            { postsPerPage !== -1 && (
                                <RangeControl
                                    label={ __( 'Number of Posts to Show', 'aubsmugg' ) }
                                    value={ postsPerPage }
                                    onChange={ ( value ) => {
                                        setAttributes({ postsPerPage: value });
                                    }}                                    
                                    min={ 1 }
                                    max={ 40 }
                                />
                            ) }
                            <hr />
                            <label 
                                className="blocks-base-control__label" 
                                htmlFor="post-types" 
                                style={{fontSize: "0.875rem", marginBottom: "0.875rem", display: "block", fontWeight: "bold"}}
                            >
                                { __( 'Post Types', 'aubsmugg' ) }
                            </label>
                            <FormTokenField
                                label={ __( 'Select Post Types', 'your-text-domain' ) }
                                value={ postTypes }
                                suggestions={ postTypeNames }
                                onChange={ (selectedTypes) => 
                                    setAttributes({
                                        postTypes: selectedTypes
                                    })
                                }
                            />
                            <hr />
                            <label 
                                className="blocks-base-control__label" 
                                htmlFor="Categories" 
                                style={{fontSize: "0.875rem", marginBottom: "0.875rem", display: "block", fontWeight: "bold"}}
                            >
                                { __( 'Categories', 'aubsmugg' ) }
                            </label>
                            <FormTokenField
                                label={__('Select Categories', 'aubsmugg')}
                                value={categories.selected.map(catId => 
                                    (categoriesData.find(cat => cat.id === catId) || {}).name || ''
                                )}
                                suggestions={categoriesData.map(cat => cat.name)}
                                onChange={(selectedCategories) => 
                                    setAttributes({
                                        categories: {
                                            ...categories,
                                            selected: selectedCategories.map(catName => 
                                                (categoriesData.find(cat => cat.name === catName) || {}).id || ''
                                            ),
                                        }
                                    })
                                }
                            />
                            <RadioControl
                                label={__('Category Relationship', 'aubsmugg')}
                                selected={categories.relation}
                                help = {__("If you select 'AND', only posts that are in all selected categories will be displayed. If you select 'OR', posts that are in any of the selected categories will be displayed.", "aubsmugg")}
                                options={[
                                    { label: __('AND', 'aubsmugg'), value: 'AND' },
                                    { label: __('OR', 'aubsmugg'), value: 'OR' },
                                ]}
                                onChange={(value) => 
                                    setAttributes({
                                        categories: {
                                            ...categories,
                                            relation: value,
                                        }
                                    })
                                }
                            />
                            <hr />
                            <label 
                                className="blocks-base-control__label" 
                                htmlFor="Tags" 
                                style={{fontSize: "0.875rem", marginBottom: "0.875rem", display: "block", fontWeight: "bold"}}
                            >
                                { __( 'Tags', 'aubsmugg' ) }
                            </label>
                            <FormTokenField
                                label={__('Select Tags', 'aubsmugg')}
                                value={tags.selected.map(tagId => 
                                    (tagsData.find(tag => tag.id === tagId) || {}).name || ''
                                )}
                                suggestions={tagsData.map(tag => tag.name)}
                                onChange={(selectedTags) => 
                                    setAttributes({
                                        tags: {
                                            ...tags,
                                            selected: selectedTags.map(tagName => 
                                                (tagsData.find(tag => tag.name === tagName) || {}).id || ''
                                            ),
                                        }
                                    })
                                }
                            />
                            <RadioControl
                                label={__('Tag Relationship', 'aubsmugg')}
                                selected={tags.relation}
                                help = {__("If you select 'AND', only posts that are in all selected tags will be displayed. If you select 'OR', posts that are in any of the selected tags will be displayed.", "aubsmugg")}
                                options={[
                                    { label: __('AND', 'aubsmugg'), value: 'AND' },
                                    { label: __('OR', 'aubsmugg'), value: 'OR' },
                                ]}
                                onChange={(value) => 
                                    setAttributes({
                                        tags: {
                                            ...tags,
                                            relation: value,
                                        }
                                    })
                                }
                            />
                        </>
                    ) : (
                        <>
                            <hr />
                            <label 
                                className="blocks-base-control__label" 
                                htmlFor="manualPosts" 
                                style={{fontSize: "0.875rem", marginBottom: "0.875rem", display: "block", fontWeight: "bold"}}
                            >
                                { __( 'Choose Posts', 'aubsmugg' ) }
                            </label>
                            <FormTokenField
                                label={__('Select Posts', 'aubsmugg')}
                                value={manualPosts.map(postId => 
                                    (allPostsData.find(post => post.ID === postId) || {}).post_title || ''
                                ).filter(postTitle => postTitle !== '')}                                
                                suggestions={allPostsData.map(post => post.post_title)}
                                onChange={(selectedPosts) => 
                                    setAttributes({
                                        manualPosts: selectedPosts.map(postTitle => 
                                            (allPostsData.find(post => post.post_title === postTitle) || {}).ID || null
                                        ).filter(postId => postId !== null),
                                    })
                                }                                
                            />
                        </>
                    )}
                </PanelBody>
            </InspectorControls>
            <div {...blockProps}>
                <ServerSideRender
                    block="aubsmugg/display-posts"
                    attributes={ attributes }
                />
            </div>
        </Fragment>
    );
 }