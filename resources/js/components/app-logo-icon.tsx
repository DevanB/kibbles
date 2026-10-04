import type { SVGAttributes } from 'react';

export default function AppLogoIcon(props: SVGAttributes<SVGElement>) {
    return (
        <svg
            {...props}
            viewBox="0 0 32 32"
            xmlns="http://www.w3.org/2000/svg"
            aria-hidden
        >
            <text
                x="16"
                y="24"
                textAnchor="middle"
                fontSize="22"
                fontWeight="700"
                fontFamily="ui-sans-serif, system-ui, sans-serif"
                fill="currentColor"
            >
                K
            </text>
        </svg>
    );
}
