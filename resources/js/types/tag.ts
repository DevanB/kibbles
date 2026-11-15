export type Tag = {
    id: string;
    name: string;
};

export type ManageTag = Tag & {
    usage: string;
    warning: string;
};
