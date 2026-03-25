import { StyledProvider }   from "@yith/styles";
import { __experimentalDocumentProvider as DocumentProvider } from "@yith/components";

export const Root = ( { children } ) => {
	const [doc, setDoc] = React.useState();

	return <div ref={( node ) => node && node?.ownerDocument && setDoc( node.ownerDocument )}>
		{doc &&
		 <DocumentProvider document={doc}>
			 <StyledProvider document={doc}>
				 {children}
			 </StyledProvider>
		 </DocumentProvider>
		}
	</div>
};
